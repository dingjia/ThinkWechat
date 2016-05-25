DROP TABLE IF EXISTS `opensns_Wechat`;
DROP TABLE IF EXISTS `opensns_Wechat_type`;
DROP TABLE IF EXISTS `opensns_Wechat_bookmark`;
DROP TABLE IF EXISTS `opensns_Wechat_lzl_reply`;
DROP TABLE IF EXISTS `opensns_Wechat_post`;
DROP TABLE IF EXISTS `opensns_Wechat_post_reply`;

/*删除menu相关数据*/
set @tmp_id=0;
select @tmp_id:= id from `opensns_menu` where `title` = '微信';
delete from `opensns_menu` where  `id` = @tmp_id or (`pid` = @tmp_id  and `pid` !=0);
delete from `opensns_menu` where  `title` = '微信';

delete from `opensns_menu` where  `url` like 'Wechat/%';