import sys
from unittest.mock import MagicMock, patch

# Mock all dependencies to prevent database and model loading during import
mock_qdrant = MagicMock()
mock_models = MagicMock()
mock_st = MagicMock()
mock_faiss = MagicMock()

sys.modules['qdrant_client'] = mock_qdrant
sys.modules['qdrant_client.models'] = mock_models
sys.modules['sentence_transformers'] = mock_st
sys.modules['faiss'] = mock_faiss

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
        mock_embed.encode.return_value = [0.1] * 384
        mock_rerank.predict.return_value = [0.9]

        # Mock OpenAI Chat Completion response
        mock_completion = MagicMock()
        mock_completion.choices = [MagicMock()]
        mock_completion.choices[0].message.content = "Answer suggestions: [S1] | [S2] | [S3]"
        mock_completion.choices[0].finish_reason = "stop"
        mock_ai_client.return_value.chat.completions.create.return_value = mock_completion

        # Test Case 1: Query in French, selected language in English
        # Should translate query to English, but output should be in English.
        mock_detect_translate.return_value = {
            "detected_language": "French",
            "translated_text": "What is the capital coefficient?"
        }
        
        server.process_question("Quel est le coefficient de capital?", target_lang="English")
        
        # Verify language instructions appended to system prompt
        called_messages = mock_ai_client.return_value.chat.completions.create.call_args[1]["messages"]
        system_prompt = called_messages[0]["content"]
        self.assertIn("Although the user asked in French, you MUST respond to the user and write the Suggestions in English", system_prompt)

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
        mock_detect_translate.return_value = {
            "detected_language": "French",
            "translated_text": "What is the capital coefficient?"
        }
        
        server.process_question("Quel est le coefficient de capital?", target_lang=None)
        
        called_messages = mock_ai_client.return_value.chat.completions.create.call_args[1]["messages"]
        system_prompt = called_messages[0]["content"]
        self.assertIn("You MUST respond to the user and write the Suggestions in French", system_prompt)

        # Test Case 4: Analyze case in French
        # Should verify that the system prompt instructs output values in French.
        mock_ai_client.return_value.chat.completions.create.reset_mock()
        mock_request = MagicMock()
        mock_request.case_details = {"debt": 100}
        mock_request.client_alias = "Alias"
        mock_request.context_overview = "Overview"
        mock_request.user_profile = ""
        mock_request.lang = "French"
        
        server.analyze_case(mock_request)
        
        called_messages = mock_ai_client.return_value.chat.completions.create.call_args[1]["messages"]
        system_prompt = called_messages[0]["content"]
        self.assertIn("All textual values in the output JSON (such as recommendations, challenges, style tips, reading reasons/titles) MUST be written in French", system_prompt)

        # Test Case 5: Generate plan in French
        # Should verify that the system prompt instructs output values in French.
        mock_ai_client.return_value.chat.completions.create.reset_mock()
        mock_request_plan = MagicMock()
        mock_request_plan.case_data = {"debt": 100}
        mock_request_plan.analysis_data = {"ai_recommendations": []}
        mock_request_plan.user_profile = ""
        mock_request_plan.lang = "French"
        
        server.generate_plan(mock_request_plan)
        
        called_messages = mock_ai_client.return_value.chat.completions.create.call_args[1]["messages"]
        system_prompt = called_messages[0]["content"]
        self.assertIn("All textual values in the output JSON (such as executive summary, objectives, action plan steps/titles, recommendations, CSF, plan B) MUST be written in French", system_prompt)

        print("[SUCCESS] All language logic tests passed!")

if __name__ == "__main__":
    unittest.main()
