<?php


namespace sookie\uploader;

use yii\base\BaseObject;

/**
 * Class ThumpImageHandler
 * @package uploader
 * 缩略图
 */
class ThumpImageHandler extends BaseObject
{
    private $src;
    private $imageInfo;
    private $image;
    private $width = 150;
    private $height = 150;

    public function setSrc($src){

        $this->src = $src;

        return $this;
    }

    public function setWidth($width){

        $this->width = $width;

        return $this;
    }

    public function setHeight($height){

        $this->height = $height;

        return $this;
    }

    /**
     * 打开图片
     */
    public function openImage(){
        list($width, $height, $type, $attr) = getimagesize($this->src);

        $this->imageInfo = array(
            'width'=>$width,
            'height'=>$height,
            'type'=>image_type_to_extension($type,false),
            'attr'=>$attr
        );

        $fun = 'imagecreatefrom'.$this->imageInfo['type'];

        $this->image = $fun($this->src);
    }

    /**
    操作图片
     */
    public function thumpImage(){

        $new_width = $this->width;
        $new_height = $this->height;
        $image_thump = imagecreatetruecolor($new_width,$new_height);
        //将原图复制带图片载体上面，并且按照一定比例压缩,极大的保持了清晰度
        imagecopyresampled($image_thump,$this->image,0,0,0,0,$new_width,$new_height,$this->imageInfo['width'],$this->imageInfo['height']);
        imagedestroy($this->image);

        $this->image = $image_thump;
    }
    /**
    输出图片
     */
    public function showImage(){

        $this->openImage();

        $this->thumpImage();

        header('Content-Type: image/'.$this->imageInfo['type']);

        $funcs = "image".$this->imageInfo['type'];

        $funcs($this->image);
    }

    /**
     * @param $path
     * 保存的全路径
     * 保存图片到硬盘
     */
    public function saveImage($path){

        $this->openImage();

        $this->thumpImage();

        $funcs = "image".$this->imageInfo['type'];

        $funcs($this->image,$path);
    }

    public function getImage(){
        $this->openImage();

        $this->thumpImage();

        return $this->image;
    }

    /**
    销毁图片
     */
    public function destructImage(){
        imagedestroy($this->image);
    }

    public function isImg()
    {
        $fileName = $this->src;

        $file     = fopen($fileName, "rb");
        $bin      = fread($file, 2);  // 只读2字节

        fclose($file);
        $strInfo  = @unpack("C2chars", $bin);
        $typeCode = intval($strInfo['chars1'].$strInfo['chars2']);
        $fileType = '';

        if($typeCode == 255216 /*jpg*/ || $typeCode == 7173 /*gif*/ || $typeCode == 13780 /*png*/)
        {
            return $typeCode;
        }else {
            // echo '"仅允许上传jpg/jpeg/gif/png格式的图片！';
            return false;
        }
    }
}
