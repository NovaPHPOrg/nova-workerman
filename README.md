# nova-workerman

开发热重载依赖 `conf.d/99-custom.ini` 中的 `opcache.validate_timestamps=1`。
不要再额外放一个把该值改成 `0` 的 ini，否则改 tpl/config/php 后 reload 看起来像没生效。

```bash
./workerman.sh start
```