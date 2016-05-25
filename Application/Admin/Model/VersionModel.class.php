<?php

namespace Admin\Model;

use Think\Model;

class VersionModel extends Model
{
    protected $versionPath = './Data/version.ini';

    /**检查是否有新的更新
     * @return bool
     */
    public function checkUpdate()
    {
        $result = S('admin_update');
        if ($result === false) {

            if ($this->getNextVersion() == '') {
                $result = 0;
            } else {
                $result = 1;
            }
            S('admin_update', $result, 600);
        }
        return $result;

    }
    public function cleanCheckUpdateCache(){
        S('admin_update',null);
    }

    /**获取当前的版本号
     * @return string
     */
    public function getCurrentVersion()
    {
        $version = file_get_contents($this->versionPath);
        $this->refreshVersions();
        $version = $this->where(array('name' => $version))->find();

        return $version;
    }

    /**设置当前版本号
     * @param $name 版本号
     * @return int|void
     */
    public function setCurrentVersion($name)
    {
        return file_put_contents($this->versionPath, $name);
    }

    /**
     * 重新从服务器获取所有的版本信息并更新本地
     */
    public function refreshVersions()
    {
        $opts = array(
            'http'=>array(
                'method'=>'GET',
                'timeout'=>5, //设置超时，单位是秒，可以试0.1之类的float类型数字
            )
        );
        $context = stream_context_create($opts);
        $content = file_get_contents(C('__CLOUD__') . cloudU('Appstore/Update/versions'),$context);
        $versions = json_decode($content, true);

        foreach ($versions as $key => $v) {
            $version = $this->where(array('name' => $v['name']))->find();
            if (!$version) {
                $this->add($v);
            } else {
                unset($v['update_time']);
                $version = $v + $version;
                $this->save($version);
            }
        }
        $this->where(array('name' => array('not in', getSubByKey($versions, 'name'))))->delete();
    }

    public function getNextVersion()
    {
        $versions = $this->order('number asc')->select();
        $currentVersion = $this->getCurrentVersion();
        foreach ($versions as $v) {
            if (version_compare($v['name'], $currentVersion['name']) == 1) {
                return $v;
            }
        }
        return '';
    }
}
