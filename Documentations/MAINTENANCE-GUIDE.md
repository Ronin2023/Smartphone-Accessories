# TechCompare Maintenance Mode Guide

## 🎯 Overview
Complete guide for managing maintenance mode with special access for Admins and Editors.

---

## 🔐 Access Levels

### Public Users
- **During Maintenance**: See maintenance page with countdown timer
- **Normal Operation**: Full site access

### Admins & Editors
- **Always**: Can access admin panel (`/admin/`)
- **Special Access**: Can access full site during maintenance using special links
- **Control**: Can enable/disable maintenance mode

---

## 📋 How to Turn OFF Maintenance Mode

### Method 1: Maintenance Manager (Easiest)
1. Go to: `admin/maintenance-manager.php`
2. Click **"Disable Maintenance Mode"** button
3. Confirm when prompted
4. ✅ Done! Site is now public

### Method 2: Admin Dashboard
1. Login to `admin/dashboard.php`
2. Find "Maintenance Mode Status" section
3. Click **"Disable"** button
4. Site is now accessible

### Method 3: Direct URL
```
maintenance-control.php?action=disable&key=YOUR_ADMIN_KEY
```
Get your admin key from: `get-admin-key.php`

### Method 4: Manual (Emergency)
If all else fails, manually edit `.htaccess`:
1. Open `.htaccess` file
2. Delete everything between:
   ```
   # Maintenance Mode - Auto Generated
   ...
   # End Maintenance Mode
   ```
3. Save file
4. Site is restored

---

## 🔑 Special Access for Admins & Editors

### Generate Special Access Links
1. Login to admin panel
2. Go to: `admin/special-access.php`
3. Copy your personal access link
4. Share with authorized team members

### Special Access Features
- ✅ **Personalized Links**: Unique token for each user
- ✅ **24-Hour Validity**: Links expire daily for security
- ✅ **Role-Based**: Only Admins & Editors can generate
- ✅ **Secure**: Encrypted tokens stored in database

### Types of Access Links

#### 1. Personal Token Link (Most Secure)
```
https://yoursite.com/?special_access=TOKEN_HERE
```
- Unique per user
- Tracked in database
- Expires after 24 hours
- Best for sharing with team

#### 2. Admin Bypass Link (Simple)
```
https://yoursite.com/?admin_bypass=1
```
- Works for any admin/editor
- No expiration
- Simple to use
- Good for quick access

#### 3. Secure Admin Bypass
```
https://yoursite.com/?admin_bypass=1&key=ADMIN_KEY
```
- Requires daily admin key
- Most secure option
- Changes daily
- Use for sensitive access

---

## 🛠️ Maintenance Mode Control

### Enable Maintenance Mode

#### Via Maintenance Manager:
1. Go to `admin/maintenance-manager.php`
2. Fill in:
   - Page Title
   - Message to Users
   - Contact Email
3. Click **"Enable Maintenance Mode"**
4. Confirm

#### Via Dashboard:
1. `admin/dashboard.php`
2. Click **"Enable Maintenance"** in Maintenance Mode section

#### What Happens When Enabled:
- ✅ Public users see maintenance page
- ✅ Admin panel remains accessible
- ✅ Special access links work
- ✅ Assets (CSS, JS, images) load normally
- ✅ `.htaccess` rules automatically created

### Disable Maintenance Mode
See "How to Turn OFF Maintenance Mode" section above.

---

## 📱 Quick Access Pages

### For Admins/Editors:
- **Dashboard**: `admin/dashboard.php`
- **Maintenance Manager**: `admin/maintenance-manager.php` ⭐
- **Special Access Generator**: `admin/special-access.php` 🔑
- **Maintenance Control**: `maintenance-control.php`
- **Get Admin Key**: `get-admin-key.php`

### For Emergency:
- **Quick Disable**: `disable-maintenance.php`
- **Test Admin Access**: `admin/test-access.php`

---

## 🔒 Security Features

### Daily Key Rotation
Admin keys change daily using formula:
```php
MD5(SITE_NAME + TODAY_DATE)
```
Example: `MD5('TechCompare' + '2025-10-19')`

### Access Token System
- Stored in database with user info
- 24-hour expiration
- Can be revoked anytime
- Tracks usage

### Protected URLs
During maintenance, these are **always accessible**:
- `/admin/*` - All admin pages
- `/maintenance.php` - Maintenance display
- `/maintenance-control.php` - Control panel
- `/get-admin-key.php` - Key generator
- `/disable-maintenance.php` - Emergency disable
- `/css/*`, `/js/*`, `/assets/*` - Static files
- Any URL with `?special_access=TOKEN`
- Any URL with `?admin_bypass=1`

---

## 🚀 Common Workflows

### Scenario 1: Quick Maintenance
```
1. Go to admin/maintenance-manager.php
2. Click "Enable Maintenance Mode"
3. Do your work in admin panel
4. Click "Disable Maintenance Mode"
5. Done!
```

### Scenario 2: Team Maintenance
```
1. Enable maintenance mode
2. Go to admin/special-access.php
3. Copy personal access link
4. Share with team via Slack/Email
5. Team can access site using special link
6. Disable when done
```

### Scenario 3: Emergency Access
```
Lost access? Try these in order:
1. Go to admin/dashboard.php (should always work)
2. Try: index.html?admin_bypass=1
3. Get key from get-admin-key.php
4. Use: maintenance-control.php?action=disable&key=KEY
5. Manual: Edit .htaccess file
```

---

## 📊 Current Status Check

### Check if Maintenance is Active:
1. Visit: `maintenance-control.php?action=status&key=ADMIN_KEY`
2. Or check: `admin/dashboard.php` (shows status banner)
3. Or try: Visit homepage (if you see maintenance page, it's active)

---

## 🎨 Customization

### Maintenance Page Messages
Edit in `admin/maintenance-manager.php` when enabling:
- Page Title
- User Message
- Estimated Duration
- Contact Email

### Maintenance Page Design
Edit `maintenance.php` for:
- Colors and styling
- Countdown timer
- Feature list
- Social links

---

## ⚡ Quick Reference

### Today's Admin Key
Visit: `get-admin-key.php` (shows current key)

### Essential URLs
```
Enable:  maintenance-control.php?action=enable&key=KEY
Disable: maintenance-control.php?action=disable&key=KEY
Status:  maintenance-control.php?action=status&key=KEY
Access:  index.html?admin_bypass=1
```

### Emergency Contacts
- Admin Dashboard: Always accessible at `/admin/`
- Maintenance Control: `maintenance-control.php`
- Get Key: `get-admin-key.php`

---

## 🆘 Troubleshooting

### Problem: Can't access admin panel
**Solution**:
- Try: `admin/dashboard.php?admin_bypass=1`
- Or manually remove maintenance rules from `.htaccess`

### Problem: Lost admin key
**Solution**:
- Visit: `get-admin-key.php`
- Or login to admin panel (key-free access)
- Or use: `?admin_bypass=1` (works without key)

### Problem: Special access link not working
**Solution**:
- Generate new link (valid 24 hours only)
- Check if token expired
- Use simple `?admin_bypass=1` instead

### Problem: Maintenance mode won't disable
**Solution**:
1. Try `disable-maintenance.php`
2. Or manually edit `.htaccess`
3. Check database: `settings` table → `maintenance_enabled` → set to `0`

---

## 📝 Notes

- **Admin keys change daily** at midnight for security
- **Special access tokens expire** after 24 hours
- **Admin panel** (`/admin/`) is always accessible
- **Simple bypass** (`?admin_bypass=1`) works without key
- **Emergency**: Can always manually edit `.htaccess`

---

## 🎯 Best Practices

1. ✅ Always use **Maintenance Manager** for simple control
2. ✅ Generate **Special Access Links** for team members
3. ✅ Keep **Admin Key** saved (changes daily)
4. ✅ Test access before enabling maintenance
5. ✅ Notify users before maintenance
6. ✅ Set realistic duration estimates
7. ✅ Disable maintenance when done
8. ✅ Regular backup of `.htaccess` file

---

## 📞 Support

For issues:
1. Check this guide first
2. Try emergency access methods
3. Check `.htaccess` file
4. Review database `settings` table
5. Check PHP error logs

---

*Last Updated: October 19, 2025*
*TechCompare Maintenance System v2.0*
