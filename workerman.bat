@echo off
rem 作用：自动写入自定义 ini，再调用 bootstrap.php
setlocal enabledelayedexpansion

set "DIR=%~dp0"
set "CONF_DIR=%DIR%conf.d"
set "INI_FILE=%CONF_DIR%\99-custom.ini"

rem 首次运行时写入自定义 ini（若已存在则跳过）
if not exist "%INI_FILE%" (
    if not exist "%CONF_DIR%" mkdir "%CONF_DIR%"
    >"%INI_FILE%" (
        echo opcache.enable=1
        echo opcache.enable_cli=1
        echo opcache.validate_timestamps=0
        echo opcache.save_comments=0
        echo opcache.enable_file_override=1
        echo opcache.huge_code_pages=1
        echo.
        echo memory_limit=512M
        echo.
        echo opcache.jit_buffer_size=128M
        echo opcache.jit=tracing
        echo.
        echo disable_functions=header,header_remove,headers_sent,headers_list,http_response_code,setcookie,session_create_id,session_id,session_name,session_save_path,session_status,session_start,session_write_close,session_regenerate_id,session_unset,session_get_cookie_params,session_set_cookie_params,session_set_save_handler,set_time_limit,connection_aborted
        echo ;disable_classes=
    )
)

rem 把 conf.d 目录追加到 PHP_INI_SCAN_DIR，然后执行 Workerman
set "PHP_INI_SCAN_DIR=%CONF_DIR%"
php "%DIR%bootstrap.php" %*
endlocal
