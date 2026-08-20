import sys
from unittest.mock import MagicMock, patch

# Mock all external dependencies to allow standalone unit testing without docker
def passthrough_decorator(*args, **kwargs):
    def decorator(fn):
        return fn
    return decorator

mock_fastapi = MagicMock()
mock_fastapi.FastAPI.return_value.get = passthrough_decorator
mock_fastapi.FastAPI.return_value.post = passthrough_decorator
mock_fastapi.FastAPI.return_value.middleware = passthrough_decorator
mock_fastapi.Header = MagicMock(return_value=None)
sys.modules['fastapi'] = mock_fastapi

for mod in [
    'numpy', 'openai', 'dotenv',
    'fastapi.staticfiles', 'fastapi.middleware.cors',
    'pydantic', 'qdrant_client', 'qdrant_client.models',
    'sentence_transformers', 'faiss', 'langdetect'
]:
    sys.modules[mod] = MagicMock()

# Mock the database client and model loads in the global scope of server.py
with patch('qdrant_client.QdrantClient'), \
     patch('sentence_transformers.SentenceTransformer'), \
     patch('sentence_transformers.CrossEncoder'):
    
    # Add API directory to path
    import os
    from pathlib import Path
    api_dir = str(Path(__file__).resolve().parent.parent / "api")
    if api_dir not in sys.path:
        sys.path.insert(0, api_dir)
        
    import server

import unittest

class TestLanguageLogic(unittest.TestCase):
    def setUp(self):
        # Reset OpenAI client mock
        server._client = MagicMock()

    @patch('server.detect_and_translate')
    @patch('server.get_ai_client')
    @patch('server.rerank_model')
    @patch('server.embed_model')
    @patch('server.vector_db')
    def test_process_question_language_combinations(self, mock_db, mock_embed, mock_rerank, mock_ai_client, mock_detect_translate):
        # Mock vector DB search results
        mock_point = MagicMock()
        mock_point.payload = {
            "text": "The banking capital coefficient is 8%.",
            "page": 1,
            "images": ["image1.png"]
        }
        mock_db.query_points.return_value.points = [mock_point]
        mock_embed.encode.return_value.tolist.return_value = [0.1] * 384
        mock_rerank.predict.return_value = [0.9]
        server.hybrid_search = MagicMock(return_value=[mock_point])

        # Mock OpenAI Chat Completion response
        mock_completion = MagicMock()
        mock_completion.choices = [MagicMock()]
        mock_completion.choices[0].message.content = "Answer suggestions: [S1] | [S2] | [S3]"
        mock_completion.choices[0].finish_reason = "stop"
        mock_ai_client.return_value.chat.completions.create.return_value = mock_completion

        # Test Case 1: Query in English, selected language in English
        server.process_question("What is the capital coefficient?", target_lang="English")
        called_messages = mock_ai_client.return_value.chat.completions.create.call_args[1]["messages"]
        system_prompt = called_messages[0]["content"]
        self.assertIn("[BOOK TABLE OF CONTENTS", system_prompt)

        # Test Case 2: Query in English, selected language in French
        # Output should be forced to French.
        mock_detect_translate.return_value = {
            "detected_language": "English",
            "translated_text": "What is the capital coefficient?"
        }
        
        server.process_question("What is the capital coefficient?", target_lang="French")
        
        called_messages = mock_ai_client.return_value.chat.completions.create.call_args[1]["messages"]
        system_prompt = called_messages[0]["content"]
        self.assertIn("You MUST respond to the user and write the Suggestions in French", system_prompt)

        # Test Case 3: Query in French, no target language specified
        # Output should fall back to French (detected language).
        with patch('server.detect_language_local', return_value="French"):
            server.process_question("Quel est le coefficient de capital?", target_lang=None)
            
            called_messages = mock_ai_client.return_value.chat.completions.create.call_args[1]["messages"]
            system_prompt = called_messages[0]["content"]
            self.assertIn("You MUST respond to the user and write the Suggestions in French", system_prompt)

        # Test Case 4: Analyze case in French
        # Should verify that the system prompt instructs output values in French.
        mock_completion_case = MagicMock()
        mock_completion_case.choices = [MagicMock()]
        mock_completion_case.choices[0].message.content = '{"ai_recommendations": [], "suggested_readings": [], "ai_challenges": [], "negotiation_style_tips": [], "confidence_score": 90}'
        mock_completion_case.choices[0].finish_reason = "stop"
        mock_ai_client.return_value.chat.completions.create.return_value = mock_completion_case

        mock_request = MagicMock()
        mock_request.case_details = {"debt": 100}
        mock_request.client_alias = "Alias"
        mock_request.context_overview = "Overview"
        mock_request.user_profile = ""
        mock_request.lang = "French"
        mock_request.client_history = []
        
        server.analyze_case(mock_request)
        
        called_messages = mock_ai_client.return_value.chat.completions.create.call_args[1]["messages"]
        system_prompt = called_messages[0]["content"]
        self.assertIn("All textual values in the output JSON (such as recommendations, challenges, style tips, reading reasons/titles/chapters) MUST be written in French", system_prompt)

        # Test Case 5: Generate plan in French
        # Should verify that the system prompt instructs output values in French.
        mock_completion_plan = MagicMock()
        mock_completion_plan.choices = [MagicMock()]
        mock_completion_plan.choices[0].message.content = '{"executive_summary": "Summary", "meeting_objectives": [], "action_plan": {"phase_1_before": {"title": "P1", "steps": [], "readings": []}, "phase_2_during": {"title": "P2", "steps": [], "readings": []}, "phase_3_after": {"title": "P3", "steps": [], "readings": []}}, "strategic_recommendations": [], "critical_success_factors": [], "plan_b": []}'
        mock_completion_plan.choices[0].finish_reason = "stop"
        mock_ai_client.return_value.chat.completions.create.return_value = mock_completion_plan

        mock_request_plan = MagicMock()
        mock_request_plan.case_data = {"debt": 100}
        mock_request_plan.analysis_data = {"ai_recommendations": []}
        mock_request_plan.user_profile = ""
        mock_request_plan.lang = "French"
        mock_request_plan.client_history = []
        
        server.generate_plan(mock_request_plan)
        
        called_messages = mock_ai_client.return_value.chat.completions.create.call_args[1]["messages"]
        system_prompt = called_messages[0]["content"]
        self.assertIn("All textual values in the output JSON (such as executive summary, objectives, action plan steps/titles, recommendations, CSF, plan B) MUST be written in French", system_prompt)

    def test_normalize_suggested_readings(self):
        # 1. English - missing page_no with chapter name
        en_raw = [
            {
                "chapter": "Chapter 11: How to Handle Objections, Price Negotiation",
                "title": "Handling Price Objections and Defending Fees",
                "time": "15 mins",
                "reason": "Provides pricing objection handling."
            },
            {
                "chapter": "11",
                "title": "Objections - Pricing",
                "time": "10 mins",
                "reason": "Deals with price resistance."
            },
            {
                "chapter": "Page 1",
                "title": "Prospecting and Survival",
                "page_no": 1,
                "time": "12 mins",
                "reason": "Guide to prospecting."
            },
            {
                "chapter": "413",
                "title": "Handling Price Objections and Defending Fees",
                "time": "15 mins",
                "reason": "Direct page reference to Section on page 413."
            }
        ]

        en_res = server.normalize_suggested_readings(en_raw, "English")
        self.assertEqual(len(en_res), 4)
        for item in en_res:
            self.assertIn("page_no", item)
            self.assertIn("page", item)
            self.assertGreater(item["page_no"], 2, f"page_no must be > 2, got {item['page_no']}")
            self.assertEqual(item["page_no"], item["page"])
            self.assertNotIn("Page 1", item["chapter"])
            self.assertNotEqual(item["chapter"], "413")

        self.assertEqual(en_res[0]["page_no"], 405)
        self.assertEqual(en_res[1]["page_no"], 405)
        self.assertEqual(en_res[2]["page_no"], 245)
        self.assertEqual(en_res[3]["page_no"], 413)

        # 2. French - normalisation
        fr_raw = [
            {
                "chapter": "Chapitre 10 : Persuasion, USP et signaux d'achat",
                "title": "La méthode FAB-USP",
                "time": "15 mins",
                "reason": "Différenciation de valeur."
            },
            {
                "chapter": "Chapitre 11",
                "title": "Traitement des objections et négociation du prix",
                "page_no": 1,
                "time": "10 mins",
                "reason": "Négociation tarifaire."
            },
            {
                "chapter": "Chapitre 7",
                "title": "La prospection ou l'art de survivre",
                "time": "10 mins",
                "reason": "Création du top 50 prospects."
            }
        ]

        fr_res = server.normalize_suggested_readings(fr_raw, "French")
        self.assertEqual(len(fr_res), 3)
        for item in fr_res:
            self.assertGreater(item["page_no"], 2)
            self.assertEqual(item["page_no"], item["page"])
            self.assertNotIn("Page 1", item["chapter"])

        self.assertEqual(fr_res[0]["page_no"], 361)
        self.assertEqual(fr_res[1]["page_no"], 405)
        self.assertEqual(fr_res[2]["page_no"], 245)

    def test_all_chapters_page_mapping(self):
        # Verify every chapter resolves to its authentic starting page (> 2) in both EN and FR
        en_chapters = [
            ("Introduction", 17),
            ("Chapter 1", 37),
            ("Chapter 2", 69),
            ("Chapter 3", 113),
            ("Chapter 4", 145),
            ("Chapter 5", 181),
            ("Chapter 6", 213),
            ("Chapter 7", 245),
            ("Chapter 8", 299),
            ("Chapter 9", 331),
            ("Chapter 10", 361),
            ("Chapter 11", 405),
            ("Chapter 11 bis", 429),
            ("Chapter 12", 451),
            ("Chapter 13", 467),
            ("Chapter 14", 485),
        ]
        for ch_label, expected_page in en_chapters:
            raw = [{"chapter": ch_label, "title": ch_label, "time": "10 mins", "reason": "Test"}]
            norm = server.normalize_suggested_readings(raw, "English")
            self.assertEqual(norm[0]["page_no"], expected_page, f"{ch_label} should resolve to page {expected_page}, got {norm[0]['page_no']}")
            self.assertGreater(norm[0]["page_no"], 2)

        fr_chapters = [
            ("Introduction", 17),
            ("Chapitre 1", 37),
            ("Chapitre 2", 69),
            ("Chapitre 3", 113),
            ("Chapitre 4", 145),
            ("Chapitre 5", 181),
            ("Chapitre 6", 213),
            ("Chapitre 7", 245),
            ("Chapitre 8", 299),
            ("Chapitre 9", 331),
            ("Chapitre 10", 361),
            ("Chapitre 11", 405),
            ("Chapitre 11 bis", 429),
            ("Chapitre 12", 451),
            ("Chapitre 13", 467),
            ("Chapitre 14", 485),
        ]
        for ch_label, expected_page in fr_chapters:
            raw = [{"chapter": ch_label, "title": ch_label, "time": "10 mins", "reason": "Test"}]
            norm = server.normalize_suggested_readings(raw, "French")
            self.assertEqual(norm[0]["page_no"], expected_page, f"{ch_label} should resolve to page {expected_page}, got {norm[0]['page_no']}")
            self.assertGreater(norm[0]["page_no"], 2)

    def test_schema_includes_page_no(self):
        schema = server.ANALYZE_CASE_RESPONSE_FORMAT["json_schema"]["schema"]
        readings_schema = schema["properties"]["suggested_readings"]["items"]
        self.assertIn("page_no", readings_schema["properties"])
        self.assertEqual(readings_schema["properties"]["page_no"]["type"], "integer")
        self.assertIn("page_no", readings_schema["required"])

        print("[SUCCESS] All language logic and suggested readings tests passed!")

if __name__ == "__main__":
    unittest.main()


