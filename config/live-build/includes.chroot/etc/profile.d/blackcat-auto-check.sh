# Black Cat automatic quick-check launcher.
# Runs only when the bootloader adds blackcat.check=1.

case " $(cat /proc/cmdline 2>/dev/null) " in
  *" blackcat.check=1 "*)
    if [ -z "${BLACKCAT_AUTO_CHECK_DONE:-}" ] && [ -x /usr/local/bin/blackcat-check ]; then
      export BLACKCAT_AUTO_CHECK_DONE=1
      clear 2>/dev/null || true
      /usr/local/bin/blackcat-check
      printf '\nPHOTO THIS SCREEN for the test record.\n'
      printf 'Press Enter when you want to continue to the shell. '
      read -r _blackcat_continue
      clear 2>/dev/null || true
    fi
    ;;
esac
