<?php

namespace support;

/**
 * 模拟上传文件类
 * 用于将下载的远程文件转换为上传文件对象
 */
class UploadFile
{
    protected $file;
    protected $name;
    protected $mimeType;
    
    public function __construct($file, $name, $mimeType = null)
    {
        $this->file = $file;
        $this->name = $name;
        $this->mimeType = $mimeType ?: 'application/octet-stream';
    }
    
    /**
     * 检查文件是否有效
     */
    public function isValid()
    {
        return file_exists($this->file) && is_readable($this->file);
    }
    
    /**
     * 获取文件扩展名
     */
    public function getUploadExtension()
    {
        return pathinfo($this->name, PATHINFO_EXTENSION);
    }
    
    /**
     * 获取文件 MIME 类型
     */
    public function getUploadMimeType()
    {
        return $this->mimeType;
    }
    
    /**
     * 获取文件大小
     */
    public function getSize()
    {
        return filesize($this->file);
    }
    
    /**
     * 移动文件
     */
    public function move($destination, $name = null)
    {
        $name = $name ?: basename($this->file);
        $target = $destination . DIRECTORY_SEPARATOR . $name;
        
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }
        
        if (copy($this->file, $target)) {
            return $target;
        }
        
        return false;
    }
    
    /**
     * 获取文件路径
     */
    public function getPathname()
    {
        return $this->file;
    }
    
    /**
     * 获取文件名
     */
    public function getUploadName()
    {
        return $this->name;
    }
}
