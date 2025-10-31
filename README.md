# BTC Stalker - Multi-Timeframe Dashboard

Real-time Bitcoin price tracking dashboard with multi-timeframe analysis, live trade monitoring, and integrated news feeds.

**Current Version:** v13.35
**Live Site:** [h8s-site](https://github.com/classic079/h8s-site)

---

## Features

### Multi-Timeframe Price Charts
- **8 Timeframes:** 30 days, 24 hours, 12 hours, 8 hours, 1 hour, 10 minutes, 5 minutes, 1 minute
- Real-time price updates via Coinbase WebSocket
- Color-coded charts (green for above average, orange for below)
- High/Low price tracking per timeframe
- Configurable chart visibility via Settings modal

### Volume Analysis
- Live volume bars showing buy/sell distribution
- Volume spike alerts (1.5x average triggers visual flash)
- Per-bar volume labels and buy/sell difference indicators
- Separate volume tracking for all timeframes

### Trade Monitoring
- **Top Trades Tracking:** Shows largest buys and sells per timeframe
- **Buy/Sell Bias Gauge:** Visual representation of buy vs sell pressure (1h, 10m, 5m, 1m only)
- **Trade Metrics:** Trades per minute, VWAP, average trade size, median trade size
- **Yellow Flash Alerts:** New large trades flash yellow (duration: 10 seconds per BTC, minimum 5 seconds)
- **Firebase Persistence:** Top trades saved for 30d, 24h, 12h, 8h, 1h timeframes (survives page refreshes)

### Live News Feed
Aggregates headlines from multiple sources:
- **Trump Truth** (TruthSocial RSS)
- **Fox News**
- **Reuters** (24h via Google News)
- **CNBC**
- **CoinDesk**
- **Cointelegraph**

Updates every 3 minutes, displays 15 most recent headlines.

### Additional Price Tickers
Header shows 24h prices with percentage change for:
- ETH (Ethereum)
- XRP (Ripple)
- ADA (Cardano)
- Gold (XAU/USD)

### Theme Support
- Dark mode (default)
- Light mode
- Persistent theme selection via localStorage

---

## Technical Architecture

### Data Sources

**Real-time Price Data:**
- Primary: Coinbase WebSocket (`wss://ws-feed.exchange.coinbase.com`)
- Fallback: Coinbase REST API polling (activates after 3.5s WebSocket silence)

**Historical Data:**
- Coinbase candle API for initial load:
  - 30d: 6-hour candles
  - 24h/12h/8h: 5-minute candles
  - 1h/10m/5m/1m: 1-minute candles

**24h Statistics:**
- Coinbase Stats API for BTC 24h change
- Coinbase Stats API for ETH, XRP, ADA prices

**Gold Price:**
- TwelveData API (API key: `830bdfca25d44bfe9992c9872d0693f5`)

**News Feeds:**
- RSS feeds via rss2json.com CORS proxy

### Data Management

**Sampling Strategy:**
- Different sample rates per timeframe to optimize performance
- 30d: 1-hour intervals
- 24h: 5-minute intervals
- 10m: 0.5-second intervals
- 1m: 0.1-second intervals

**Volume Tracking:**
- All trades captured (no sampling) for accurate volume calculations
- Time-binned volume bars with locked boundaries for short timeframes

**Top Trades Persistence:**
- Firebase Realtime Database stores top buys/sells
- Only 1h+ timeframes persisted (10m/5m/1m too volatile)
- Prevents duplicate saves during initial load via `isLoadingFromFirebase` flag

### Firebase Configuration
```javascript
{
  apiKey: "AIzaSyAy7bMRwm98jdSgg40KUvXPuUONd8o6KLc",
  authDomain: "btc-stalker.firebaseapp.com",
  databaseURL: "https://btc-stalker-default-rtdb.firebaseio.com",
  projectId: "btc-stalker",
  storageBucket: "btc-stalker.firebasestorage.app",
  messagingSenderId: "127552164700",
  appId: "1:127552164700:web:c2e56579b4946b7d44e799"
}
```

---

## Recent Changes

### v13.35 (Current)
- **Added news feed health monitoring** - Tracks successful news updates with timestamp and count
- **News update logging** - Console logs show "📊 News update #X completed at [time]" for each successful fetch
- **Enhanced error tracking** - Errors now show time since last successful news update to diagnose stalling issues

### v13.34
- **Added health monitoring** - Tracks when trades stop flowing and logs warnings to diagnose 5m chart blanking issue
- **Added array clearing detection** - Logs error if 5m data array gets unexpectedly cleared
- **Trade flow tracking** - Monitors last trade time to detect silent WebSocket failures

### v13.33
- **Limited white outline to NY Times only** - Only NY Times source name has white glow, other sources display normal for better visual clarity

### v13.32
- **Added white text-shadow to news sources** - NY Times and other dark-colored source names now have white glow for better readability on dark background

### v13.31
- **Re-enabled [No Title] filter** - Filters out Trump Truth re-truths/shares while keeping original posts with proper titles
- **Cleaner news feed** - Only shows original Trump posts, not shared content

### v13.30
- **Added Live Ticker** - New scrolling trade feed showing real-time BTC trades (last 100)
- **Trade details** - Displays buy/sell side, price, size, USD value, and timestamp for each trade
- **Color-coded sides** - Green for buys, orange for sells
- **Toggleable in Settings** - Enable/disable Live Ticker like other chart panels

### v13.29
- **Expanded news window** - Increased news panel height to 800px (+50px) to show more article content
- **Better news visibility** - Last article now fully visible without cutoff

### v13.28
- **Time-windowed top trades** - Top trades now expire from lists after their timeframe window (12h trades drop off 12h chart, etc.)
- **Dynamic trade lists** - Trades older than the chart's timeframe are automatically pruned

### v13.27
- **Live volume bar for short timeframes** - First (rightmost) bar now updates live for 10m, 5m, and 1m charts
- **Improved real-time tracking** - Current volume bin grows as trades come in instead of waiting for completion

### v13.26
- **Added mobile detection** - Automatically redirects to mobile.html when accessed from phone or tablet
- **User agent detection** - Checks for Android, iOS, and other mobile devices

### v13.25
- **Added world news sources** - Expanded feeds to include BBC World, NY Times, Wall Street Journal, and Yahoo News
- **Total 10 news sources** - Now aggregating from Trump Truth, Fox News, Reuters, CNBC, BBC, NYT, WSJ, Yahoo, CoinDesk, Cointelegraph

### v13.24
- **Force UTC timestamp parsing** - All RSS timestamps now parsed as UTC instead of local time, fixing "future" timestamp issues
- **Automatic timezone conversion** - Appends 'Z' to timestamps without timezone info to force UTC interpretation

### v13.23
- **Changed to relative time display** - Shows "Xm ago", "Xh ago" instead of absolute times for easier tracking
- **Added future timestamp detection** - Shows "Xm future" if article timestamps are in the future (RSS feed issue indicator)

### v13.22
- **Added raw RSS date logging** - Debug output showing original date strings from RSS feeds to diagnose timezone issues

### v13.21
- **Extended news panel further** - Increased to 750px (+10% more space)
- **Added timezone debugging** - Console logs to diagnose timezone display issues

### v13.20
- **Increased news panel height** - Extended to 680px (+10% from previous) for better article visibility
- **Fixed timezone display** - Times now show in user's local timezone instead of forcing US Eastern

### v13.19
- **Adjusted news display** - Changed from 15 to 10 articles and increased height to 620px for better visibility
- **Improved article spacing** - More room for each article to be readable

### v13.18
- **Fixed news panel height** - Set to 340px to match other panels, no scrollbar needed
- **Improved time display** - Changed from "Just now" to actual time (e.g., "2:30 PM") for better tracking

### v13.17
- **Enhanced Trump Truth debugging** - Added detailed per-item logging to diagnose why posts aren't appearing
- **Added validation failure tracking** - Logs items that fail title or date validation

### v13.16
- **Removed [No Title] filter** - Disabled filtering to allow all Trump Truth posts through (including re-truths)
- **Added development workflow documentation** - Created comprehensive version bump process in README

### v13.15
- **Refreshed Trump Truth feed integration** - Removed and re-added to resolve potential caching issues
- **Enhanced news debugging** - Added detailed logging to track Trump Truth filter behavior
- **Removed CNN feed** - Streamlined news sources

### v13.14
- **Firebase real-time listeners disabled** - Caused infinite save loops, needs better approach
- **Top trades persistence working** - Loads from Firebase on startup
- **News feed filters** - Removes re-truths with "[No Title]" prefix

---

## Known Issues

### Trump Truth Feed Challenge
- **Issue:** Trump Truth RSS feed fetches items but they may not appear in news list
- **Cause:** Many recent posts are re-truths (shares), which have "[No Title]" prefix and get filtered out
- **Debug:** Enhanced logging added (v13.15) to show filter behavior
- **Status:** Monitoring - works when original posts are available

### Firebase Real-Time Sync
- **Issue:** Real-time listeners caused infinite update loops
- **Temporary Fix:** Listeners disabled (line 1666)
- **Impact:** Top trades persist on page load but don't sync in real-time across tabs
- **Future:** Need debouncing or change detection to prevent save loops

### RSS2JSON Rate Limits
- Free tier may have rate limits
- News feed updates every 3 minutes (could hit limits with multiple users)

---

## Settings & Configuration

### User Settings (localStorage)
Settings stored in `btcStalkerSettings`:
```javascript
{
  theme: 'dark' | 'light',
  charts: {
    '30d': boolean,
    '24h': boolean,
    // ... etc
    'news': boolean
  },
  alerts: {
    volSpike: boolean,  // Volume spike flash alerts
    tradeBlink: boolean // Large trade yellow flash
  }
}
```

### Default Visible Charts
- 24h, 8h, 1h, 5m, News Feed

### Alert Thresholds
- **Volume Spike:** 1.5x average volume
- **Large Trade Blink:** All trades in top 10/13 list

---

## Development Notes

### Why Separate Arrays?
- `win30d`, `win24h`, etc.: Sampled price data for charts
- `win30dVol`, `win24hVol`, etc.: All trades for accurate volume
- `win30dLive`, `win24hLive`, etc.: Live trades for top trades list (NOT time-windowed)

### Why Live Arrays Aren't Pruned?
The "Live" arrays (win30dLive, etc.) hold the historical high scores (top trades) that persist via Firebase. They're intentionally not pruned by time window - they accumulate the biggest trades seen over the session/Firebase history.

### Chart Drawing Performance
- Canvas uses device pixel ratio for crisp rendering (capped at 3x)
- Throttled resize handler prevents excessive redraws
- 2-minute default timeout for bash commands

---

## Files in Repository

- `index.html` - Main production dashboard (current)
- `mobile.html` - Mobile-optimized version
- `m.html` - Mobile version (duplicate)
- `staging.html` - Staging/test version
- `old.html` - Previous version backup
- `desktop.html.backup` - Desktop version backup
- `btc-test-simple.html` - Simple test file
- `CNAME` - GitHub Pages custom domain config

---

## Future Improvements

### High Priority
- Fix Firebase real-time sync (add proper debouncing)
- Improve Trump Truth feed reliability (maybe direct API instead of RSS)
- Add rate limit handling for RSS2JSON

### Nice to Have
- Export top trades as CSV
- Alert notifications (browser notifications API)
- Historical price range selector
- Custom alert thresholds in Settings
- Mobile responsive improvements
- WebSocket reconnection improvements

---

## Deployment

**Platform:** GitHub Pages
**Branch:** main
**Auto-deploy:** Enabled (pushes to main deploy automatically)

To deploy changes:
```bash
git add .
git commit -m "Description of changes"
git push origin main
```

---

## Development Workflow

### Version Bump Process

**IMPORTANT:** After EVERY change to index.html, follow this process:

1. **Update version number** in TWO places:
   - Line 6: `<title>BTC Stalker - Multi-Timeframe vX.XX</title>`
   - Line 162: `<span class="pill" style="opacity:.6">vX.XX</span>`

2. **Update README.md**:
   - Line 5: `**Current Version:** vX.XX`
   - Add entry to "Recent Changes" section with bullet points of what changed

3. **Commit with descriptive message**:
   ```bash
   git add index.html README.md
   git commit -m "Descriptive message of changes"
   git push origin main
   ```

### Version Numbering
- **Major changes** (new features, major UI changes): Increment first decimal (13.x → 14.0)
- **Minor changes** (bug fixes, small tweaks, config changes): Increment second decimal (13.15 → 13.16)

### Claude Code Instructions
When working with Claude Code, remind at start of session:
> "Please update version numbers in index.html (title + UI pill) and commit after every change"

Or add to each request:
> "Update version and commit when done"

---

## Troubleshooting

### "Connecting..." stuck
- Check browser console for WebSocket errors
- Verify Coinbase API is accessible
- Should auto-fallback to REST API after 3.5 seconds

### News feed not loading
- Check console for RSS fetch errors
- rss2json.com may be rate limited
- Individual feeds may be temporarily down

### Charts not updating
- Check WebSocket connection status (bottom-left pill)
- Verify Firebase connection in console
- Try hard refresh (Ctrl+Shift+R)

### Top trades disappeared
- Check Firebase console for data
- `isLoadingFromFirebase` flag may be stuck (check console)
- Try refreshing page

---

**Last Updated:** 2025-10-30
**Maintainer:** John (classic079)
