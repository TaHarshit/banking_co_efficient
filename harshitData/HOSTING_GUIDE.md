# Hosting Guide for PDF Q&A Banking Project

## Can You Run This on GoDaddy Linux Shared Hosting?

**Short Answer: No, not fully.** ❌

The Laravel part will work, but the **Python PDF Q&A service will NOT work** on GoDaddy shared hosting due to these limitations:

### Why GoDaddy Shared Hosting Won't Work:

1. **No Python Support** - Shared hosting doesn't allow you to run custom Python applications
2. **No Port Access** - You can't run services on custom ports (like port 8000)
3. **No Process Control** - You can't run background services (FastAPI server)
4. **No SSH/Terminal Access** - Limited or no command line access
5. **No Virtual Environment** - Can't install Python packages

---

## ✅ Recommended Hosting Solutions

### **Option 1: VPS (Virtual Private Server) - RECOMMENDED**

This gives you full control and is affordable.

**Providers:**
- **DigitalOcean** - $6/month (Droplet)
- **Linode** - $5/month
- **Vultr** - $6/month
- **AWS Lightsail** - $5/month
- **Hostinger VPS** - $5-10/month

**Requirements:**
- Ubuntu 20.04 or 22.04 LTS
- 1GB RAM minimum (2GB recommended)
- Python 3.8+
- PHP 8.1+
- MySQL/MariaDB
- Root/sudo access

---

### **Option 2: Cloud Platform (More Scalable)**

**For Laravel (Main App):**
- Deploy on shared hosting or any PHP hosting

**For Python Service (Separate):**
- **Railway.app** - Free tier available, easy deployment
- **Render.com** - Free tier available
- **Heroku** - Free tier (with limitations)
- **Google Cloud Run** - Pay per use
- **AWS Lambda** - Serverless option

---

## 📋 Server Requirements

### **Minimum Requirements:**

#### For Laravel Application:
- PHP 8.1 or higher
- Composer
- MySQL 5.7+ or MariaDB 10.3+
- Apache/Nginx web server
- PHP Extensions:
  - BCMath
  - Ctype
  - Fileinfo
  - JSON
  - Mbstring
  - OpenSSL
  - PDO
  - Tokenizer
  - XML

#### For Python PDF Service:
- Python 3.8 or higher
- pip (Python package manager)
- 512MB RAM minimum (1GB recommended)
- Ability to run background processes
- Port 8000 available (or any custom port)
- SSH access

---

## 🚀 Recommended Setup for Your Project

### **Setup 1: Single VPS (Easiest)**

Everything runs on one server:

```
VPS Server (Ubuntu 22.04)
├── Apache/Nginx (Port 80/443)
│   └── Laravel Application
└── Python FastAPI Service (Port 8000)
    └── PDF Q&A Service
```

**Pros:**
- Simple setup
- Everything in one place
- Easy to manage
- Cost-effective ($5-10/month)

**Cons:**
- Single point of failure
- Limited scalability

---

### **Setup 2: Hybrid (Laravel on Shared + Python on VPS)**

```
GoDaddy Shared Hosting
└── Laravel Application (Main App)
    ↓ (Makes HTTP requests to)
VPS/Cloud Service
└── Python FastAPI Service (PDF Q&A)
```

**Pros:**
- Use existing GoDaddy hosting
- Only pay for small VPS for Python service
- Separation of concerns

**Cons:**
- Two servers to manage
- Need to configure CORS properly
- Network latency between services

---

### **Setup 3: All Cloud (Most Scalable)**

```
Cloud Hosting (e.g., DigitalOcean App Platform)
├── Laravel Application
└── Python Service (Separate container)
```

**Pros:**
- Highly scalable
- Automatic deployments
- Professional setup

**Cons:**
- More expensive
- More complex setup

---

## 💰 Cost Comparison

| Option | Monthly Cost | Difficulty | Scalability |
|--------|-------------|------------|-------------|
| **VPS (Single Server)** | $5-10 | Medium | Medium |
| **GoDaddy + Small VPS** | $10-15 | Medium | Medium |
| **Cloud Platform** | $15-30 | Hard | High |
| **GoDaddy Shared Only** | ❌ Won't Work | - | - |

---

## 🎯 My Recommendation for You

### **Best Option: DigitalOcean Droplet ($6/month)**

1. **Get a DigitalOcean Droplet:**
   - Ubuntu 22.04 LTS
   - 1GB RAM ($6/month)
   - Full root access

2. **Install Required Software:**
   ```bash
   # Update system
   sudo apt update && sudo apt upgrade -y
   
   # Install Apache, PHP, MySQL
   sudo apt install apache2 php8.1 php8.1-{cli,common,mysql,xml,xmlrpc,curl,gd,imagick,cli,dev,imap,mbstring,opcache,soap,zip,intl,bcmath} mysql-server -y
   
   # Install Python 3 and pip
   sudo apt install python3 python3-pip python3-venv -y
   
   # Install Composer
   curl -sS https://getcomposer.org/installer | php
   sudo mv composer.phar /usr/local/bin/composer
   ```

3. **Deploy Your Project:**
   - Upload Laravel app to `/var/www/html/banking_co_efficient`
   - Set up Python service in `pdf_service/`
   - Configure Apache virtual host
   - Start Python service with systemd

4. **Configure Domain:**
   - Point your domain to DigitalOcean IP
   - Set up SSL with Let's Encrypt (free)

---

## 📝 Quick Setup Guide for VPS

### Step 1: Get VPS
Sign up for DigitalOcean, Linode, or Vultr

### Step 2: Initial Server Setup
```bash
# Connect via SSH
ssh root@your-server-ip

# Create new user
adduser yourusername
usermod -aG sudo yourusername

# Set up firewall
ufw allow OpenSSH
ufw allow 'Apache Full'
ufw enable
```

### Step 3: Install LAMP Stack
```bash
sudo apt update
sudo apt install apache2 mysql-server php libapache2-mod-php php-mysql -y
```

### Step 4: Install Python
```bash
sudo apt install python3 python3-pip python3-venv -y
```

### Step 5: Deploy Your Project
```bash
# Upload files via SFTP or git
cd /var/www/html
sudo git clone your-repo.git banking_co_efficient

# Set permissions
sudo chown -R www-data:www-data banking_co_efficient
sudo chmod -R 755 banking_co_efficient

# Install Laravel dependencies
cd banking_co_efficient
composer install
php artisan key:generate

# Set up Python service
cd pdf_service
python3 -m venv venv
source venv/bin/activate
pip install fastapi uvicorn faiss-cpu numpy openai python-multipart
```

### Step 6: Configure Services
Create systemd service for Python (see DEPLOYMENT_CHECKLIST.md)

---

## ⚠️ Important Notes

1. **GoDaddy Shared Hosting Limitations:**
   - Cannot run Python applications
   - Cannot open custom ports
   - Cannot run background services
   - Limited to PHP/MySQL only

2. **If You Must Use GoDaddy:**
   - Deploy Laravel on GoDaddy
   - Deploy Python service on separate VPS/cloud
   - Update `PDF_SERVICE_URL` in .env to point to VPS IP
   - Configure CORS in Python service to allow GoDaddy domain

3. **Security:**
   - Always use HTTPS in production
   - Keep API keys secure
   - Update CORS settings
   - Use firewall rules

---

## 🎉 Conclusion

**For your project, I recommend:**
1. **Get a DigitalOcean Droplet** ($6/month) - Best value
2. **Deploy everything on one VPS** - Simplest setup
3. **Follow the DEPLOYMENT_CHECKLIST.md** - Step-by-step guide included

**Alternative if you want to keep GoDaddy:**
1. Keep Laravel on GoDaddy
2. Deploy Python service on Railway.app (free tier) or small VPS
3. Update PDF_SERVICE_URL to point to the Python service

---

## 📚 Additional Resources

### VPS Providers
- [DigitalOcean](https://www.digitalocean.com/) - $6/month droplets
- [Linode](https://www.linode.com/) - $5/month plans
- [Vultr](https://www.vultr.com/) - $6/month instances
- [AWS Lightsail](https://aws.amazon.com/lightsail/) - $5/month

### Cloud Platforms for Python Service
- [Railway.app](https://railway.app/) - Free tier available
- [Render.com](https://render.com/) - Free tier available
- [Heroku](https://www.heroku.com/) - Free tier (limited)

### Deployment Documentation
- `DEPLOYMENT_CHECKLIST.md` - Complete deployment steps
- `PDF_SERVICE_INTEGRATION_GUIDE.md` - Service integration details
- `pdf_service/README.md` - Python service documentation

---

## 🔧 Troubleshooting

### Common Issues

**Python Service Won't Start:**
- Check Python version: `python3 --version`
- Verify port 8000 is available: `sudo lsof -i :8000`
- Check logs: `sudo journalctl -u pdf-qa-service`

**Laravel Not Working:**
- Check PHP version: `php --version`
- Verify Apache config: `sudo apache2ctl configtest`
- Check Laravel logs: `tail -f storage/logs/laravel.log`

**Connection Issues:**
- Verify firewall settings: `sudo ufw status`
- Check if services are running: `sudo systemctl status apache2 pdf-qa-service`
- Test connectivity: `curl http://localhost:8000/docs`

---

## 📞 Support

If you encounter issues:

1. Check the documentation files in this project
2. Review service logs
3. Verify all requirements are met
4. Test each component separately

**Remember:** This project requires both PHP (Laravel) and Python (FastAPI) to work together. Ensure both services are running properly.
