# Black Cat automatic quick-check display hook.
# Runs only when the bootloader adds blackcat.check=1.
#
# IMPORTANT: /usr/local/bin/blackcat-check is executed once by
# blackcat-check.service as root. This login hook is display-only so a
# normal live user never attempts to overwrite the root-owned evidence files.

case " $(cat /proc/cmdline 2>/dev/null) " in
  *" blackcat.check=1 "*)
    if [ -z "${BLACKCAT_AUTO_CHECK_DONE:-}" ]; then
      export BLACKCAT_AUTO_CHECK_DONE=1
      clear 2>/dev/null || true

      report=/run/blackcat/quick-check.txt

      # The service runs Before=getty@tty1.service, so the report should
      # normally already exist. Allow a short bounded wait in case login and
      # service output race on unusual hardware.
      _blackcat_wait=0
      while [ ! -s "$report" ] && [ "$_blackcat_wait" -lt 20 ]; do
        sleep 0.1
        _blackcat_wait=$((_blackcat_wait + 1))
      done

      if [ -r "$report" ] && [ -s "$report" ]; then
        cat "$report"
        printf 'Report: %s\n' "$report"
        if [ -s /run/blackcat/quick-check.json ]; then
          printf 'JSON:   %s\n' /run/blackcat/quick-check.json
        fi
      else
        printf '\nBLACK CAT - QUICK CHECK\n'
        printf 'Automatic root-owned report was not available.\n'
        printf 'Do not rerun the evidence collector as the live user.\n'
        printf 'Classify this test as FAILED and photograph this screen.\n'
      fi

      printf '\nPHOTO THIS SCREEN for the test record.\n'
      printf 'Press Enter when you want to continue to the shell. '
      read -r _blackcat_continue
      clear 2>/dev/null || true
    fi
    ;;
esac
