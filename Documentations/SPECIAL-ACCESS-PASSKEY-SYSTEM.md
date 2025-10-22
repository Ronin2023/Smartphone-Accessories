# Special Access Passkey System Documentation

## 📋 Table of Contents
1. [Overview](#overview)
2. [How It Works](#how-it-works)
3. [Features](#features)
4. [Admin Guide](#admin-guide)
5. [User Experience](#user-experience)
6. [Technical Details](#technical-details)
7. [Security](#security)
8. [Troubleshooting](#troubleshooting)

---

## 🎯 Overview

The Special Access Passkey System provides **secure, granular access control** for your website during maintenance mode. Unlike traditional maintenance bypass methods that only unlock the homepage, this system grants **complete site access** using a dual authentication approach: Access Link + Passkey.

### Key Benefits
- ✅ **Full Site Access**: Users can browse the entire website during maintenance
- ✅ **Dual Authentication**: Both access link AND passkey required
- ✅ **Session Control**: Only 1 active session per passkey
- ✅ **Instant Revocation**: Admin can terminate access anytime
- ✅ **Audit Trail**: Complete logging of all access attempts
- ✅ **Time-Bound**: Access automatically expires when maintenance ends

---

## 🔄 How It Works

### Authentication Flow

```
┌─────────────────────────────────────────────────────────────────┐
│ 1. ADMIN CREATES TOKEN                                          │
│    └─> Generates: Access Link + Unique Passkey                 │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ 2. ADMIN SHARES CREDENTIALS                                     │
│    └─> Sends both link and passkey to developer/editor         │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ 3. USER CLICKS ACCESS LINK                                      │
│    └─> Redirected to passkey verification page                 │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ 4. USER ENTERS PASSKEY                                          │
│    └─> System validates: Token + Passkey + Session Status      │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│ 5. ACCESS GRANTED                                               │
│    └─> Full site access until maintenance ends or revoked      │
└─────────────────────────────────────────────────────────────────┘
```

### Maintenance Mode Behavior

| Scenario | Without Special Access | With Special Access Link | With Valid Session |
|----------|----------------------|------------------------|-------------------|
| Visit index.php | ❌ Maintenance Page | ✅ Passkey Prompt | ✅ Full Access |
| Visit products.php | ❌ Maintenance Page | ❌ Maintenance Page | ✅ Full Access |
| Visit admin panel | ✅ Admin Login | ✅ Admin Login | ✅ Admin Login |

---

## 🚀 Features

### 1. Unique Passkey Generation
- **Format**: `XXXX-XXXX-XXXX-XXXX` (16 characters)
- **Character Set**: Excludes similar-looking characters (0, O, 1, I, etc.)
- **Randomness**: Cryptographically secure generation
- **User-Friendly**: Auto-formats as user types

### 2. Session Management
- **Single Session Enforcement**: Only 1 active session per passkey
- **Automatic Cleanup**: Expired sessions removed periodically
- **Session Tracking**: IP address, user agent, timestamps logged
- **Concurrent Prevention**: New login invalidates previous session

### 3. Access Control
- **Instant Revocation**: Admin can revoke access immediately
- **Token Reactivation**: Previously revoked tokens can be reused
- **Time-Bound Access**: Automatically expires with maintenance mode
- **Granular Permissions**: Per-token control

### 4. Audit & Monitoring
- **Access Logs**: Every verification attempt recorded
- **Usage Statistics**: Total uses, last accessed timestamp
- **Session Details**: IP, user agent, duration tracking
- **Status Dashboard**: Real-time overview of tokens and sessions

---

## 👨‍💼 Admin Guide

### Creating a New Token

1. **Navigate to Admin Panel**
   - Go to: `Admin Dashboard > Special Access`
   - Or direct URL: `https://yoursite.com/admin/special-access.php`

2. **Fill the Form**
   ```
   Name/Role: John Doe - Frontend Developer
   Email: john@example.com (optional)
   Description: Emergency database migration access
   ```

3. **Generate Credentials**
   - Click "Generate Token & Passkey"
   - **IMPORTANT**: Save credentials immediately!
   
4. **Modal Display**
   ```
   🔗 Access Link:
   https://yoursite.com?special_access=a1b2c3d4e5f6...
   
   🔑 Passkey:
   ABC3-XY7Z-QW9R-MN4P
   ```

5. **Copy Both Credentials**
   - Use "Copy" buttons for accuracy
   - Send both to the user securely

### Sharing Credentials Securely

**✅ RECOMMENDED:**
- Send via encrypted messaging (Signal, WhatsApp)
- Send link and passkey in separate messages
- Use company internal communication tools
- Share over phone call for ultra-sensitive access

**❌ NOT RECOMMENDED:**
- Plain text email (can be intercepted)
- Public chat channels
- Unencrypted messaging platforms
- Shared documents without encryption

### Monitoring Active Sessions

**Token Status Badges:**
- 🟢 **Active** - Token is valid but no active session
- 🔵 **Active Session** - User is currently accessing site
- 🔴 **Revoked** - Token has been disabled

**Statistics Dashboard:**
```
┌─────────────────────────────────────────────────┐
│ Total Tokens: 12                                │
│ Active Tokens: 10                               │
│ Active Sessions: 3                              │
│ Revoked Tokens: 2                               │
└─────────────────────────────────────────────────┘
```

### Revoking Access

**When to Revoke:**
- User's work is completed
- Emergency: suspected security breach
- Token was shared with wrong person
- Maintenance period ending early

**How to Revoke:**
1. Find the token in the list
2. Click "Revoke Access" button
3. Confirm the action
4. **Result**: Session immediately terminated

**Reactivating Tokens:**
- Previously revoked tokens can be reactivated
- Same access link and passkey remain valid
- Useful if revoked by mistake

### Best Practices

1. **Token Naming**
   - Use clear, descriptive names
   - Include role or purpose
   - Example: "Jane Smith - QA Testing" ✅
   - Example: "User 1" ❌

2. **Description Field**
   - Document the reason for access
   - Include ticket/task reference numbers
   - Example: "TASK-1234: Fix checkout bug during maintenance"

3. **Regular Cleanup**
   - Revoke tokens after use
   - Review active sessions weekly
   - Delete old unused tokens

4. **Security Hygiene**
   - Never reuse tokens for different people
   - Revoke immediately if compromised
   - Monitor unusual access patterns

---

## 👤 User Experience

### Accessing the Site

1. **Receive Credentials**
   - Admin sends you two things:
     - Access Link (long URL)
     - Passkey (XXXX-XXXX-XXXX-XXXX format)

2. **Click Access Link**
   - Opens passkey verification page
   - Beautiful purple gradient interface
   - Animated key icon

3. **Enter Passkey**
   - Type or paste: `ABC3-XY7Z-QW9R-MN4P`
   - Auto-formats as you type
   - Hyphens added automatically

4. **Success!**
   - Redirected to homepage
   - Full site access granted
   - Browse normally until maintenance ends

### What Users See

**Passkey Entry Screen:**
```
┌────────────────────────────────────────────┐
│              🔑 Animated Icon              │
│                                            │
│          Special Access Required          │
│                                            │
│  Enter your passkey to access the site    │
│  during maintenance                        │
│                                            │
│  ┌──────────────────────────────────────┐ │
│  │ [____-____-____-____]                │ │
│  └──────────────────────────────────────┘ │
│                                            │
│           [Continue Access →]             │
└────────────────────────────────────────────┘
```

### Common User Scenarios

**✅ Successful Access:**
```
User enters correct passkey
→ Session created
→ Redirected to homepage
→ Can browse all pages freely
```

**❌ Invalid Passkey:**
```
User enters wrong passkey
→ Error message displayed
→ Can try again
→ Attempt logged for security
```

**❌ Token Already in Use:**
```
Another session is active
→ Error: "Only one session allowed"
→ Contact admin to revoke other session
→ Try again after revocation
```

**❌ Revoked Token:**
```
Admin revoked access
→ Error: "Token has been revoked"
→ Existing session terminated
→ Contact admin for new token
```

---

## 🔧 Technical Details

### File Structure

```
Smartphone-Accessories/
├── includes/
│   ├── special-access-manager.php      # Core logic
│   └── special-access-middleware.php   # Page protection
├── verify-special-access.php           # Passkey entry page
├── admin/
│   └── special-access.php              # Admin interface
├── index.php                           # Protected homepage
├── products.php                        # Protected products page
├── compare.php                         # Protected compare page
└── contact.php                         # Protected contact page
```

### Database Schema

**Table: special_access_tokens**
```sql
CREATE TABLE special_access_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token VARCHAR(64) NOT NULL UNIQUE,
    passkey VARCHAR(20) NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NULL,
    description TEXT NULL,
    is_active TINYINT(1) DEFAULT 1,
    max_sessions INT DEFAULT 1,
    usage_count INT DEFAULT 0,
    last_used_at DATETIME NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    revoked_at DATETIME NULL,
    revoked_by INT NULL
);
```

**Table: special_access_sessions**
```sql
CREATE TABLE special_access_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token_id INT NOT NULL,
    session_id VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NULL,
    terminated_at DATETIME NULL,
    FOREIGN KEY (token_id) REFERENCES special_access_tokens(id)
);
```

**Table: special_access_logs**
```sql
CREATE TABLE special_access_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    details TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (token_id) REFERENCES special_access_tokens(id)
);
```

### Core Classes & Methods

**SpecialAccessManager Class**

```php
class SpecialAccessManager {
    // Token Management
    public function createToken($name, $email, $description, $createdBy)
    public function revokeToken($tokenId)
    public function reactivateToken($tokenId)
    public function getAllTokens()
    public function getTokenDetails($tokenId)
    
    // Passkey Verification
    public function verifyPasskey($token, $passkey)
    public function hasActiveSession()
    
    // Session Management
    public function terminateSession($sessionId)
    public function cleanExpiredSessions()
    
    // Logging & Audit
    public function getAccessLogs($tokenId, $limit = 50)
    public function logAction($tokenId, $action, $details = null)
}
```

**Middleware Function**

```php
// includes/special-access-middleware.php
function checkSpecialAccess() {
    if (!isMaintenanceActive()) {
        return; // Site is live, no protection needed
    }
    
    if (isWhitelistedPage() || hasAdminBypass()) {
        return; // Allow whitelisted pages and admin
    }
    
    if (hasActiveSpecialAccessSession()) {
        return; // User has valid special access
    }
    
    if (hasSpecialAccessLink()) {
        redirectToPasskeyVerification();
    }
    
    redirectToMaintenancePage();
}
```

### Token Generation Algorithm

```php
// Access Token (64 characters)
$token = bin2hex(random_bytes(32));
// Output: a1b2c3d4e5f6...7890 (64 hex chars)

// Passkey (16 characters)
$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Excludes 0,O,1,I
$passkey = '';
for ($i = 0; $i < 16; $i++) {
    $passkey .= $chars[random_int(0, strlen($chars) - 1)];
    if (($i + 1) % 4 === 0 && $i < 15) {
        $passkey .= '-'; // Add hyphen every 4 chars
    }
}
// Output: ABC3-XY7Z-QW9R-MN4P
```

### Session Storage

```php
// Session Variables Set on Successful Verification
$_SESSION['special_access_verified'] = true;
$_SESSION['special_access_token_id'] = $tokenId;
$_SESSION['special_access_name'] = $name;
$_SESSION['special_access_expires'] = $expiryTime;
```

### Middleware Integration

```php
// Add to top of every protected page
<?php
require_once 'includes/special-access-middleware.php';
?>
<!DOCTYPE html>
...
```

---

## 🔒 Security

### Authentication Layers

1. **Token Validation**
   - 64-character cryptographically secure token
   - Stored in database, not in cookies
   - Validated against database on every request

2. **Passkey Verification**
   - User must know both token AND passkey
   - Passkey not transmitted in URL
   - POST request for verification

3. **Session Binding**
   - Session tied to IP address
   - User agent validation
   - Single session enforcement

4. **Time-Bound Access**
   - Expires with maintenance mode
   - Automatic session cleanup
   - Revocation takes effect immediately

### Threat Mitigation

| Threat | Mitigation |
|--------|-----------|
| **Link Sharing** | Passkey required separately |
| **Passkey Guessing** | 32^16 possible combinations |
| **Session Hijacking** | IP + User Agent binding |
| **Concurrent Access** | Single session enforcement |
| **Stolen Credentials** | Instant admin revocation |
| **Replay Attacks** | Timestamp validation |
| **SQL Injection** | Prepared statements (PDO) |
| **XSS Attacks** | Input sanitization |

### Security Best Practices

**For Admins:**
- ✅ Use HTTPS in production
- ✅ Share credentials via secure channels
- ✅ Revoke tokens after use
- ✅ Monitor access logs regularly
- ✅ Use strong descriptions for audit trails

**For Users:**
- ✅ Don't share your passkey with others
- ✅ Log out after completing work
- ✅ Use secure connection (check HTTPS)
- ✅ Report suspicious activity to admin

---

## 🔍 Troubleshooting

### Common Issues

#### 1. "Invalid passkey" Error

**Symptoms:**
- User enters correct-looking passkey
- System rejects it

**Solutions:**
```
✓ Check for typos (O vs 0, I vs 1)
✓ Verify passkey format: XXXX-XXXX-XXXX-XXXX
✓ Ensure hyphens are in correct positions
✓ Copy-paste from admin modal to avoid errors
✓ Check if token was revoked
```

#### 2. "Only one session allowed" Error

**Symptoms:**
- Valid passkey but cannot log in
- Someone else is using the token

**Solutions:**
```
1. Contact admin to check active sessions
2. Admin can revoke the other session
3. Try logging in again after revocation
4. Alternative: Admin creates new token
```

#### 3. Access Link Not Working

**Symptoms:**
- Clicking link shows maintenance page
- No passkey prompt appears

**Solutions:**
```
✓ Verify URL contains ?special_access=...
✓ Check entire link was copied (64 chars after =)
✓ Ensure maintenance mode is actually active
✓ Try pasting full URL in new incognito window
✓ Check .htaccess redirect rules
```

#### 4. Session Expires Immediately

**Symptoms:**
- Access granted but redirected to passkey again
- Session doesn't persist

**Solutions:**
```
✓ Check PHP session configuration
✓ Ensure cookies are enabled in browser
✓ Verify session.save_path is writable
✓ Check for session conflicts with other apps
✓ Review PHP error logs
```

#### 5. "Token has been revoked" Error

**Symptoms:**
- Previously working token suddenly invalid
- Access denied after successful login

**Solutions:**
```
✓ Confirm with admin if intentional revocation
✓ Check admin panel for token status
✓ Admin can reactivate the token
✓ Or create new token with fresh credentials
```

### Database Issues

**Tables Not Created:**
```bash
# Manually run table creation
mysql -u username -p database_name < database/special_access_schema.sql

# Or access via browser (tables auto-create)
https://yoursite.com/admin/special-access.php
```

**Session Cleanup:**
```php
// Manual cleanup in PHP
$manager = getSpecialAccessManager();
$manager->cleanExpiredSessions();
```

### Logs & Debugging

**Enable Debug Mode:**
```php
// In includes/config.php
define('DEBUG_SPECIAL_ACCESS', true);

// Check logs in database
SELECT * FROM special_access_logs 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
ORDER BY created_at DESC;
```

**Check Session Status:**
```php
// Temporary debug page (remove after testing)
<?php
session_start();
echo '<pre>';
print_r($_SESSION);
echo '</pre>';
```

---

## 📊 Usage Statistics

### Admin Dashboard Metrics

```
┌─────────────────────────────────────────┐
│ Total Tokens Created: 45                │
│ Active Tokens: 38                       │
│ Revoked Tokens: 7                       │
│ Active Sessions: 12                     │
│ Total Logins (all time): 523            │
│ Failed Attempts (last 24h): 3           │
└─────────────────────────────────────────┘
```

### Per-Token Statistics

- **Usage Count**: Number of successful logins
- **Last Used**: Timestamp of most recent access
- **Active Sessions**: Current live sessions
- **Created At**: Token generation date
- **Created By**: Admin username
- **Status**: Active / Revoked

---

## 🎓 Training & Support

### Quick Start Checklist

**For New Admins:**
- [ ] Access admin panel: `/admin/special-access.php`
- [ ] Create test token for yourself
- [ ] Try logging in with test credentials
- [ ] Practice revoking and reactivating tokens
- [ ] Review access logs and statistics

**For New Users:**
- [ ] Receive access link from admin
- [ ] Receive passkey separately
- [ ] Click link in browser
- [ ] Enter passkey when prompted
- [ ] Verify full site access granted

### Support Resources

- **Admin Guide**: This document, Section "Admin Guide"
- **User Instructions**: Share Section "User Experience"
- **Troubleshooting**: Section "Troubleshooting"
- **Technical Details**: Section "Technical Details"

---

## 📝 Changelog

### Version 1.0.0 (October 20, 2025)
- ✅ Initial release
- ✅ Token + Passkey dual authentication
- ✅ Single session enforcement
- ✅ Admin management interface
- ✅ Instant revocation capability
- ✅ Complete audit logging
- ✅ Auto-expiry with maintenance mode
- ✅ Beautiful UI for passkey entry
- ✅ Comprehensive documentation

---

## 🚧 Future Enhancements

### Planned Features (Not Yet Implemented)

1. **Email Notifications** ⏳
   - Requires SMTP server setup
   - Send credentials via email
   - Notify on session creation/termination

2. **QR Code Generation** 💡
   - Generate QR code for access link
   - Easier mobile access
   - Scan to open verification page

3. **Expiry Dates** 💡
   - Set custom expiration per token
   - Auto-revoke after date
   - Useful for temporary contractors

4. **Multiple Sessions** 💡
   - Allow N sessions per token
   - Useful for team access
   - Configurable per token

5. **2FA Integration** 💡
   - Additional security layer
   - Optional for high-security sites

---

## 🤝 Contributing

If you improve this system or find bugs, please:
1. Document the changes
2. Update this documentation
3. Test thoroughly before deployment
4. Share improvements with the team

---

## 📄 License

This system is proprietary and confidential.
Unauthorized distribution or use is prohibited.

---

**Last Updated**: October 20, 2025  
**Version**: 1.0.0  
**Author**: TechCompare Development Team
