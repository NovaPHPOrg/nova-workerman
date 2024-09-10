<?php
/**
 * Session 相关功能重写
 */
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Session\FileSessionHandler;

function session_abort(){
    /* @var Request $req */
    global $req;

} //  — Discard session array changes and finish session
function session_cache_expire(){

} //  — 返回/设置当前缓存的到期时间
function session_cache_limiter(){

} //  — 读取/设置缓存限制器
function session_commit(){
    session_write_close();
} //  — session_write_close 的别名
function session_create_id(){

} //  — Create new session id
function session_decode(){

} //  — 解码会话数据
function session_destroy(){

} //  — 销毁一个会话中的全部数据
function session_encode(){

} //  — 将当前会话数据编码为字符串
function session_gc(){
    /* @var Request $req */
    global $req;
    $req->session->gc();
} //  — Perform session data garbage collection
function session_get_cookie_params(){

} //  — 获取会话 cookie 参数
function session_id(){

} //  — 获取/设置当前会话 ID
function session_module_name($name = null){

} //  — 获取/设置会话模块名称
function session_name(){

} //  — 读取/设置会话名称
function session_regenerate_id(){

} //  — 使用新生成的会话 ID 更新现有会话 ID
function session_register_shutdown(){} //  — 关闭会话
function session_reset(){} //  — Re-initialize session array with original values
function session_save_path(string $path = null): ?string{
    if ($path === null) {
        return ini_get('session.save_path');
    }
    FileSessionHandler::sessionSavePath($path);
    return null;
} //  — 读取/设置当前会话的保存路径
function session_set_cookie_params(){} //  — 设置会话 cookie 参数
function session_set_save_handler(){} //  — 设置用户自定义会话存储函数
function session_start(){} //  — 启动新会话或者重用现有会话
function session_status(){} //  — 返回当前会话状态
function session_unset(){} //  — 释放所有的会话变量
function session_write_close(){} //  — Write session data and end session