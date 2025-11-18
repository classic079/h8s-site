# MySQL Setup Instructions for BTC Stalker

## Step 1: Create MySQL Database in Hostinger

1. **Log into Hostinger hPanel** - https://hpanel.hostinger.com
2. **Go to:** Websites → h8s.us → Databases → MySQL Databases
3. **Click:** "Create new database"
4. **Database Name:** `btc_stalker` (will become `u582515363_btc_stalker`)
5. **Set Password:** Choose a strong password
6. **Save the password!** You'll need it for Step 2

## Step 2: Update Database Credentials

1. **Edit file:** `/api-trades.php` on your server
2. **Find line 12:** `define('DB_PASS', '');`
3. **Replace with:** `define('DB_PASS', 'YOUR_PASSWORD_HERE');`
4. **Save the file**

## Step 3: Initialize Database Tables

**Run this URL once to create the tables:**
```
https://h8s.us/api-trades.php?action=init
```

**Expected response:**
```json
{"success":true,"message":"Database initialized"}
```

## Step 4: Test the API

**Check database stats:**
```
https://h8s.us/api-trades.php?action=stats
```

**Load trades (should be empty initially):**
```
https://h8s.us/api-trades.php?action=load
```

## Troubleshooting

### "Database connection failed"
- Check password in `api-trades.php` line 12
- Verify database name: `u582515363_btc_stalker`
- Confirm database exists in hPanel

### "Table already exists"
- Normal if you've already run init
- Database is ready to use

### "Access denied"
- Database password is incorrect
- Update line 12 in `api-trades.php`

## Database Schema

**Table:** `top_trades`

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Auto-increment primary key |
| timeframe | VARCHAR(10) | '30d', '24h', '12h', '8h', '1h' |
| trade_type | ENUM | 'buy' or 'sell' |
| price | DECIMAL(20,2) | Trade price in USD |
| size | DECIMAL(20,8) | Trade size in BTC |
| usd_value | DECIMAL(20,2) | Total USD value |
| trade_time | BIGINT | JavaScript timestamp (ms) |
| last_update | BIGINT | Last save timestamp |
| created_at | TIMESTAMP | MySQL creation time |

## Benefits vs Firebase

✅ **Faster** - Direct database queries, no network latency
✅ **Free** - Included with Hostinger hosting
✅ **Full Control** - Can query, backup, optimize as needed
✅ **Private** - Data stays on your server
✅ **No Limits** - No Firebase rate limits or quotas

## Maintenance

### Backup trades
```sql
mysqldump -u u582515363 -p u582515363_btc_stalker top_trades > backup.sql
```

### View top trades
```sql
SELECT timeframe, trade_type, COUNT(*)
FROM top_trades
GROUP BY timeframe, trade_type;
```

### Clear old data (optional)
```sql
DELETE FROM top_trades WHERE timeframe = '1h' AND trade_time < UNIX_TIMESTAMP() * 1000 - 3600000;
```
