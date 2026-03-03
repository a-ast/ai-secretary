# Scheduler

## Local Scheduling

The `ai-sec:email:new` command runs every 30 minutes via a macOS LaunchAgent.

**Plist location:** `~/Library/LaunchAgents/com.ai-sec.email.new.plist`
**Log file:** `var/log/scheduler.log`

```bash
# Check the agent is loaded
launchctl list | grep ai-sec

# Trigger a run immediately
launchctl start com.ai-sec.email.new

# Watch the log in real time
tail -f var/log/scheduler.log

# Reload after editing the plist
launchctl unload ~/Library/LaunchAgents/com.ai-sec.email.new.plist
launchctl load ~/Library/LaunchAgents/com.ai-sec.email.new.plist

# Stop scheduling (survives reboot until loaded again)
launchctl unload ~/Library/LaunchAgents/com.ai-sec.email.new.plist
```
