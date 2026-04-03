# PDF Q&A Service - Deployment Checklist

## ✅ Pre-Deployment Checklist

### Local Testing (Before Upload)

- [ ] **Set OpenAI API Key**
  ```bash
  export OPENAI_API_KEY="sk-your-api-key-here"
  ```

- [ ] **Start Python Service**
  ```bash
  cd pdf_service
  ./start_service.sh
  ```
  - Service should start on http://127.0.0.1:8000
  - No errors in console

- [ ] **Update .env File**
  ```env
  PDF_SERVICE_URL=http://127.0.0.1:8000/ask
  ```

- [ ] **Test Service Status**
  ```bash
  curl http://localhost/banking_co_efficient/api/pdf/status
  -H "api-key: YOUR_API_KEY_FROM_ENV" \
  -H "platform: WEB" \
  ```
  - Should return: `{"success": true, "message": "PDF service is running"}`

- [ ] **Test PDF Question API**
  ```bash
  curl -X POST http://localhost/banking_co_efficient/api/pdf/ask \
    -H "Content-Type: application/json" \
    -H "api-key: YOUR_API_KEY_FROM_ENV" \
  -H "platform: WEB" \
    -d '{"question": "What is in the PDF?"}'
  ```
  - Should return answer with success: true

- [ ] **Verify All Files Exist**
  ```bash
  cd /Applications/XAMPP/xamppfiles/htdocs/banking_co_efficient
  ls -la pdf_service/
  ls -la pdf_service/api/
  ls -la pdf_service/data/
  ls -la pdf_service/embeddings/
  ls -la pdf_service/vectorstore/
  ```

## 📦 Files to Upload to Server

### Core Application Files
- [ ] All Laravel files (app/, routes/, config/, etc.)
- [ ] composer.json and composer.lock
- [ ] .env.example (update .env on server)

### PDF Service Files (NEW)
- [ ] pdf_service/api/server.py
- [ ] pdf_service/scripts/*.py (4 files)
- [ ] pdf_service/data/*.json (3 files)
- [ ] pdf_service/data/input_pdf.pdf
- [ ] pdf_service/data/images/*.png (2 files)
- [ ] pdf_service/embeddings/meta.json
- [ ] pdf_service/embeddings/vectors.npy
- [ ] pdf_service/vectorstore/index.faiss
- [ ] pdf_service/start_service.sh
- [ ] pdf_service/README.md
- [ ] pdf_service/QUICK_START.md

### Documentation Files (NEW)
- [ ] PDF_SERVICE_INTEGRATION_GUIDE.md
- [ ] INTEGRATION_SUMMARY.md
- [ ] DEPLOYMENT_CHECKLIST.md (this file)

### Updated Files
- [ ] app/Http/Controllers/Api/PdfQuestionController.php (NEW)
- [ ] routes/api.php (UPDATED)
- [ ] .env.example (UPDATED)
- [ ] .gitignore (UPDATED)

## 🚀 Server Deployment Steps

### Step 1: Upload Files
```bash
# From your local machine, upload to server
scp -r banking_co_efficient/ user@your-server:/path/to/web/root/
```

Or use FTP/SFTP client to upload the entire `banking_co_efficient` folder.

### Step 2: Server Configuration

- [ ] **Install Python 3.8+ on server**
  ```bash
  python3 --version
  ```

- [ ] **Set OpenAI API Key on server**
  ```bash
  # Add to server's environment
  echo 'export OPENAI_API_KEY="sk-your-api-key"' >> ~/.bashrc
  source ~/.bashrc
  ```

- [ ] **Update .env on server**
  ```bash
  cd /path/to/banking_co_efficient
  cp .env.example .env
  nano .env
  ```
  Add:
  ```env
  PDF_SERVICE_URL=http://127.0.0.1:8000/ask
  ```

- [ ] **Make startup script executable**
  ```bash
  chmod +x pdf_service/start_service.sh
  ```

### Step 3: Start Python Service

- [ ] **Test manual start**
  ```bash
  cd pdf_service
  ./start_service.sh
  ```

- [ ] **Verify service is running**
  ```bash
  curl http://127.0.0.1:8000/docs
  ```

### Step 4: Configure Process Manager (Production)

#### Option A: Using systemd

- [ ] **Create service file**
  ```bash
  sudo nano /etc/systemd/system/pdf-qa-service.service
  ```

  ```ini
  [Unit]
  Description=PDF Q&A FastAPI Service
  After=network.target

  [Service]
  Type=simple
  User=www-data
  WorkingDirectory=/path/to/banking_co_efficient/pdf_service/api
  Environment="OPENAI_API_KEY=sk-your-api-key"
  ExecStart=/path/to/banking_co_efficient/pdf_service/venv/bin/uvicorn server:app --host 127.0.0.1 --port 8000
  Restart=always

  [Install]
  WantedBy=multi-user.target
  ```

- [ ] **Enable and start service**
  ```bash
  sudo systemctl daemon-reload
  sudo systemctl enable pdf-qa-service
  sudo systemctl start pdf-qa-service
  sudo systemctl status pdf-qa-service
  ```

#### Option B: Using supervisor

- [ ] **Install supervisor**
  ```bash
  sudo apt-get install supervisor
  ```

- [ ] **Create config file**
  ```bash
  sudo nano /etc/supervisor/conf.d/pdf-qa-service.conf
  ```

  ```ini
  [program:pdf-qa-service]
  command=/path/to/banking_co_efficient/pdf_service/venv/bin/uvicorn server:app --host 127.0.0.1 --port 8000
  directory=/path/to/banking_co_efficient/pdf_service/api
  user=www-data
  autostart=true
  autorestart=true
  environment=OPENAI_API_KEY="sk-your-api-key"
  stdout_logfile=/var/log/pdf-qa-service.log
  stderr_logfile=/var/log/pdf-qa-service-error.log
  ```

- [ ] **Start service**
  ```bash
  sudo supervisorctl reread
  sudo supervisorctl update
  sudo supervisorctl start pdf-qa-service
  sudo supervisorctl status pdf-qa-service
  ```

### Step 5: Test on Server

- [ ] **Test service status endpoint**
  ```bash
  curl http://your-domain.com/banking_co_efficient/api/pdf/status
  ```

- [ ] **Test PDF question endpoint**
  ```bash
  curl -X POST http://your-domain.com/banking_co_efficient/api/pdf/ask \
    -H "Content-Type: application/json" \
    -d '{"question": "What is in the PDF?"}'
  ```

- [ ] **Check logs**
  ```bash
  # Laravel logs
  tail -f storage/logs/laravel.log
  
  # Python service logs (if using systemd)
  sudo journalctl -u pdf-qa-service -f
  
  # Python service logs (if using supervisor)
  tail -f /var/log/pdf-qa-service.log
  ```

## 🔐 Security Checklist

- [ ] **Update CORS in server.py**
  ```python
  allow_origins=["https://your-domain.com"]
  ```

- [ ] **Secure API endpoints** (add authentication if needed)

- [ ] **Use HTTPS** in production

- [ ] **Never commit .env** to git

- [ ] **Restrict file permissions**
  ```bash
  chmod 600 .env
  chmod 755 pdf_service/start_service.sh
  ```

- [ ] **Set proper ownership**
  ```bash
  chown -R www-data:www-data /path/to/banking_co_efficient
  ```

## 🔍 Troubleshooting on Server

### Python Service Won't Start

- [ ] Check Python version: `python3 --version` (needs 3.8+)
- [ ] Check if port 8000 is available: `lsof -i :8000`
- [ ] Verify OPENAI_API_KEY is set: `echo $OPENAI_API_KEY`
- [ ] Check file permissions
- [ ] Review error logs

### Laravel Can't Connect to Python Service

- [ ] Verify Python service is running: `curl http://127.0.0.1:8000`
- [ ] Check PDF_SERVICE_URL in .env
- [ ] Review Laravel logs: `tail -f storage/logs/laravel.log`
- [ ] Check firewall settings

### No Answer Returned

- [ ] Verify OPENAI_API_KEY is valid
- [ ] Check if data files exist in pdf_service/data/
- [ ] Verify embeddings exist in pdf_service/embeddings/
- [ ] Check FAISS index exists in pdf_service/vectorstore/
- [ ] Review Python service console output

## 📊 Monitoring

- [ ] **Set up log rotation**
  ```bash
  sudo nano /etc/logrotate.d/pdf-qa-service
  ```

- [ ] **Monitor service health**
  ```bash
  # Create a cron job to check service status
  */5 * * * * curl -f http://127.0.0.1:8000 || systemctl restart pdf-qa-service
  ```

- [ ] **Monitor disk space** (embeddings and FAISS index can be large)

- [ ] **Monitor API usage** (OpenAI API costs)

## ✅ Final Verification

- [ ] Service starts automatically on server reboot
- [ ] API endpoints respond correctly
- [ ] Logs are being written
- [ ] Error handling works properly
- [ ] Performance is acceptable
- [ ] Security measures are in place

## 📝 Post-Deployment

- [ ] **Document server details**
  - Server IP/domain
  - Python service port
  - Process manager used
  - Log file locations

- [ ] **Share API documentation** with team

- [ ] **Set up monitoring/alerts** for service downtime

- [ ] **Create backup plan** for PDF data and embeddings

## 🎉 Deployment Complete!

Once all items are checked, your PDF Q&A service is fully deployed and ready for production use!

---

**Need Help?** Refer to:
- `PDF_SERVICE_INTEGRATION_GUIDE.md` - Complete integration guide
- `pdf_service/README.md` - Service documentation
- `pdf_service/QUICK_START.md` - Quick reference
