# 🚀 QUICK START GUIDE - Special Access Passkey System

## 🎯 Quick Test (5 Minutes)

### 1️⃣ Enable Maintenance Mode
```
Admin Dashboard → Settings → Maintenance → Toggle ON
```

### 2️⃣ Create Token
```
Admin Dashboard → Special Access → Generate Token & Passkey
Name: Test User
Click Generate → COPY BOTH CREDENTIALS
```

### 3️⃣ Test Access
```
Open Incognito Window → Paste Access Link → Enter Passkey → Access Granted!
```

---

## 📍 Important URLs

| Page | URL |
|------|-----|
| **Admin Panel** | `http://localhost/Smartphone-Accessories/admin/` |
| **Special Access Manager** | `http://localhost/Smartphone-Accessories/admin/special-access.php` |
| **Settings (Maintenance)** | `http://localhost/Smartphone-Accessories/admin/settings.php` |

---

## 🔑 Access Flow Diagram

```
User receives → Access Link + Passkey
                     ↓
          Clicks Access Link
                     ↓
     Redirected to Passkey Entry Page
          (Beautiful purple UI)
                     ↓
          Enters Passkey
          (XXXX-XXXX-XXXX-XXXX)
                     ↓
          System Validates:
          • Is token valid?
          • Is passkey correct?
          • Any active session?
          • Is maintenance mode on?
                     ↓
          ✅ SUCCESS!
          Full site access granted
```

---

## ✅ Expected Behaviors

### ✓ Valid Token + Passkey
- **Result**: Full site access during maintenance
- **Duration**: Until maintenance ends OR admin revokes
- **Pages**: All pages accessible (index, products, compare, contact)

### ✗ Wrong Passkey
- **Result**: Error message shown
- **Action**: Can try again
- **Logged**: Attempt recorded in database

### ✗ Token Already in Use
- **Result**: "Only one session allowed" error
- **Solution**: Admin must revoke other session first
- **Security**: Prevents credential sharing

### ✗ Revoked Token
- **Result**: "Token has been revoked" error
- **Action**: Contact admin for new token
- **Effect**: Immediate - active sessions terminated

---

## 🎨 What You'll See

### Admin Interface
```
┌─────────────────────────────────────────┐
│ STATISTICS DASHBOARD                    │
│ ┌────────┬─────────┬─────────┬────────┐│
│ │ Total  │ Active  │ Active  │Revoked ││
│ │   12   │   10    │    3    │   2    ││
│ └────────┴─────────┴─────────┴────────┘│
│                                         │
│ TOKEN CARDS:                            │
│ ┌─────────────────────────────────────┐ │
│ │ 👤 John Doe - Developer    [Active] │ │
│ │ 📧 john@example.com                 │ │
│ │ 📅 Created: Oct 20, 2025            │ │
│ │ 🔢 Used: 15 times                   │ │
│ │                                     │ │
│ │ [View] [Revoke Access]              │ │
│ └─────────────────────────────────────┘ │
└─────────────────────────────────────────┘
```

### User Passkey Entry Page
```
┌─────────────────────────────────────────┐
│                                         │
│              🔑 (Animated)              │
│                                         │
│       Special Access Required          │
│                                         │
│  Enter your passkey to access the      │
│  site during maintenance               │
│                                         │
│  ┌───────────────────────────────────┐ │
│  │ [____-____-____-____]             │ │
│  │ (Auto-formats as you type)        │ │
│  └───────────────────────────────────┘ │
│                                         │
│        [Continue Access →]             │
│                                         │
└─────────────────────────────────────────┘
```

---

## 🔍 Database Check Commands

```sql
-- Check if tables exist
SHOW TABLES LIKE 'special_access%';

-- View all tokens
SELECT * FROM special_access_tokens;

-- View active sessions
SELECT * FROM special_access_sessions WHERE is_active = 1;

-- View recent logs
SELECT * FROM special_access_logs 
ORDER BY created_at DESC LIMIT 20;
```

---

## 🐛 Troubleshooting Quick Fixes

### Issue: "Page not found" when accessing special-access.php
**Fix**: Clear browser cache, verify file exists at `admin/special-access.php`

### Issue: Passkey not being validated
**Fix**: 
1. Check database connection in `includes/config.php`
2. Verify tables created (they auto-create on first access)
3. Check PHP error logs

### Issue: Maintenance mode not working
**Fix**: 
1. Verify maintenance toggle in Settings
2. Check `maintenance_enabled` value in `settings` table
3. Ensure middleware files are present

### Issue: Session not persisting
**Fix**:
1. Check if cookies enabled in browser
2. Verify PHP session configuration
3. Check `session.save_path` is writable

---

## 📱 Test Scenarios Checklist

- [ ] **Basic Access**: Token + Passkey = Full site access ✅
- [ ] **Single Session**: Second login with same token blocked ✅
- [ ] **Revocation**: Revoked token immediately denies access ✅
- [ ] **Wrong Passkey**: Shows error, allows retry ✅
- [ ] **Admin Bypass**: Admin can access site regardless ✅
- [ ] **Statistics Update**: Usage count increments correctly ✅
- [ ] **Token Reactivation**: Can reactivate revoked tokens ✅
- [ ] **Cross-Page Access**: All pages accessible with valid session ✅

---

## 🎓 Key Features Summary

| Feature | Status | Description |
|---------|--------|-------------|
| **Dual Auth** | ✅ | Requires both link AND passkey |
| **Single Session** | ✅ | One active session per token |
| **Instant Revoke** | ✅ | Admin can terminate access anytime |
| **Auto-Expire** | ✅ | Expires when maintenance ends |
| **Audit Trail** | ✅ | Complete logging of all actions |
| **Beautiful UI** | ✅ | Animated passkey entry page |
| **Auto-Format** | ✅ | Passkey formats as user types |
| **Session Tracking** | ✅ | IP + User Agent validation |

---

## 🔒 Security Notes

- ✅ **Cryptographically secure** token generation (64 chars)
- ✅ **Session binding** to IP address and user agent
- ✅ **SQL injection protected** (PDO prepared statements)
- ✅ **XSS protected** (input sanitization)
- ✅ **No passkey in URL** (POST request only)
- ✅ **Time-bound access** (maintenance duration)

---

## 📞 Support

**Documentation**: `Documentations/SPECIAL-ACCESS-PASSKEY-SYSTEM.md` (500+ lines)

**Files Modified**:
- ✅ 4 core system files created
- ✅ 4 pages protected with middleware
- ✅ 1 admin interface
- ✅ 1 .htaccess updated
- ✅ 1 comprehensive documentation

---

## 🎉 You're Ready!

Everything is set up and verified. Start testing now!

**First Step**: Go to `http://localhost/Smartphone-Accessories/admin/special-access.php`

---

*Last Updated: October 20, 2025*  
*Version: 1.0.0*
