<?php


namespace sookie\uploader;


use yii\base\Component;
use yii\db\Query;
use yii\helpers\ArrayHelper;

/**
 * Class BigFileUploaderHandler
 * @package uploader
 * 大文件上传组件
 * mark : 有错误 需要把文件删除
 */
class FileUploaderComponent extends Component
{
    //允许得文件扩展名
    public $allowExt = [];

    //合并的临时文件位置
    public $temp_path;

    //切片临时文件
    public $chunk_path;

    //文件保存得根路径
    public $basePath;

    //web访问地址
    public $web_path;

    //数据表
    public $table;

    //db链接
    public $db;

    //文件名
    protected $fileName;

    //文件基础名，不含扩展名
    protected $fileBaseName;

    //文件扩展名
    protected $ext;

    //文件大小
    protected $size;

    //分片每片大小
    protected $chunkSize;

    //文件类型
    protected $type;

    //总共分片
    protected $totalChunk;

    //当前分片
    protected $currentChunk;

    //上传临时文件位置
    protected $temp_file;

    //保存结果,key => value, key 状态码 value 错误信息
    protected $error = [];

    //图片地址
    protected $url;

    //缩略图地址
    protected $thump_url;

    //最终文件路径
    protected $filePathComplete;

    //缩略图保存路径
    protected $thumpPath;

    //临时合并路径
    protected $fileTempPath;

    //当前临时切片保存路径
    protected $chunkTempPath;

    //上一个切片保存路径
    protected $lastChunkTempPath;

    protected $unique_id;

    protected $info;

    /**
     * @return array
     *
     */
    public function getError(){
        return $this->error;
    }

    /**
     * @var array
     * 状态码 和 说明
     */
    protected $codeMap = [
        200 =>  ["code" => 200,"msg" => "上传成功"],
        1000 => ["code" => 1000,"msg" => "文件名不能为空"],
        1001 => ["code" => 1001,"msg" => "文件缺少扩展名"],
        1002 => ["code" => 1002,"msg" => "不允许上传该类型文件"],
        1003 => ["code" => 1003,"msg" => "文件大小为空"],
        1004 => ["code" => 1004,"msg" => "分片大小不能为空"],
        1005 => ["code" => 1005,"msg" => "分片信息不能为空"],
        1006 => ["code" => 1006,"msg" => "当前分片大于了总分片数据，文件修改了？"],
        1007 => ["code" => 1007,"msg" => "临时文件不存在"],
        1008 => ["code" => 1008,"msg" => "未配置运行的文件扩展名"],
        1009 => ["code" => 1009,"msg" => "allowExt、temp_path、hunk_path、basePath需定义"],
        1010 => ["code" => 1010,"msg" => "存在同名文件"],
        1111 => ["code" => 1111,"msg" => "服务器已经存在分片","index" => 0],
        2222 => ["code" => 2222,"msg" => "切片上传完成"],
    ];

    protected function getMessage($code){
        return ArrayHelper::getValue($this->codeMap,$code,"Error");
    }

    /**
     * @return mixed
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * @param mixed $fileName
     * @return self
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @param mixed $size
     * @return self
     */
    public function setSize($size)
    {
        $this->size = $size;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getChunkSize()
    {
        return $this->chunkSize;
    }

    /**
     * @param mixed $chunkSize
     * @return self
     */
    public function setChunkSize($chunkSize)
    {
        $this->chunkSize = $chunkSize;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     * @return self
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTotalChunk()
    {
        return $this->totalChunk;
    }

    /**
     * @param mixed $totalChunk
     * @return self
     */
    public function setTotalChunk($totalChunk)
    {
        $this->totalChunk = $totalChunk;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCurrentChunk()
    {
        return $this->currentChunk;
    }

    /**
     * @param mixed $currentChunk
     * @return self
     */
    public function setCurrentChunk($currentChunk)
    {
        $this->currentChunk = $currentChunk;

        return $this;
    }

    public function __init__()
    {
        if (!$this->basePath || !$this->temp_path || !$this->chunk_path || !$this->allowExt){
            $this->error[] = $this->getMessage(1009);
            return false;
        }

        //初始化路径
        $this->basePath = \Yii::getAlias($this->basePath);
        $this->temp_path = \Yii::getAlias($this->temp_path);
        $this->chunk_path = \Yii::getAlias($this->chunk_path);

        if (!$this->fileName){
            $this->error[] = $this->getMessage(1000);
            return false;
        }else{
            $fileNameArr = explode('.',$this->fileName);
            $this->ext = array_pop($fileNameArr);
            $this->fileBaseName = implode('.',$fileNameArr);
            if(!$this->ext){
                $this->error[] = $this->getMessage(1001);
                return false;
            }
        }

        if (!$this->allowExt){
            $this->error[] = $this->getMessage(1008);
            return false;
        }else{
            if ($this->allowExt[0] == "*" || $this->allowExt == "*"){
                $this->allowExt = "*";
            }else{
                foreach ($this->allowExt as $key => $item){
                    $this->allowExt[$key] = strtoupper($item);
                }
            }
        }

        if ($this->allowExt != "*" && !in_array(strtoupper($this->ext),$this->allowExt)){
            $this->error[] = $this->getMessage(1002);
            return false;
        }

        if (!$this->size){
            $this->error[] = $this->getMessage(1003);
            return false;
        }

        if (!$this->chunkSize){
            $this->error[] = $this->getMessage(1004);
            return false;
        }

        if (!$this->totalChunk || !$this->currentChunk){
            $this->error[] = $this->getMessage(1005);
            return false;
        }

        if ($this->currentChunk > $this->chunkSize){
            $this->error[] = $this->getMessage(1006);
            return false;
        }

        $this->temp_file = $_FILES["file"]["tmp_name"];
        if (!$this->temp_file){
            $this->error[] = $this->getMessage(1007);
            return false;
        }

        //文件名
        $year = date("Y");$month = date("m");$day = date("d");
        $this->filePathComplete = $this->basePath . "/$year/$month/$day/" . $this->fileName;
        $this->thumpPath = $this->basePath . "/$year/$month/$day/" . $this->fileBaseName . "_thump." . $this->ext;

        $this->url = \Yii::$app->request->hostInfo . \Yii::getAlias($this->web_path) . "/$year/$month/$day/" . $this->fileName;
        $this->thump_url = \Yii::$app->request->hostInfo . \Yii::getAlias($this->web_path) . "/$year/$month/$day/" . $this->fileBaseName . "_thump." . $this->ext;

        $this->fileTempPath = $this->temp_path . "/" . $this->fileName;
        $this->chunkTempPath = $this->chunk_path . "/" . $this->fileBaseName . $this->currentChunk . ".part";
        $this->lastChunkTempPath = $this->chunk_path . "/" . $this->fileBaseName . ($this->currentChunk-1) . ".part";

        //创建文件夹
        $this->create_dir($this->temp_path);
        $this->create_dir($this->chunk_path);
        $this->create_dir($this->basePath . "/$year/$month/$day/");

        //所有信息验证通过后就可以往数据库记录上传信息了
        $this->unique_id = md5($this->fileName . $this->size . $this->type . $this->totalChunk . $this->chunkSize);

        //检查是否有同名文件
        if (file_exists($this->filePathComplete)){
            $this->error[] = $this->getMessage(1010);
            return false;
        }
        //检查是否已经上传过，将地址返回就行了
        $info = (new Query())->from($this->table)->where(["unique_id" => $this->unique_id,"is_finish" => 1])->one(\Yii::$app->{$this->db});
        if ($info){
            $this->url = $info["url"];
            $this->thump_url = $info["thump_url"];
            return $this->getResult();
        }

        //入库
        $this->info = (new Query())->from($this->table)->where(["unique_id" => $this->unique_id,"is_finish" => 0])->one(\Yii::$app->{$this->db});
        if ($this->info && $this->currentChunk != ($this->info["chunk"]+2)){
            //中断了，告诉前端该从那个分片开始上传
            $this->error[] = ArrayHelper::merge($this->getMessage(1111),["index" => ($this->info["chunk"]+1)]);
            return false;
        }

        return true;
    }

    public function execute(){
        //初始化
        $init_res = $this->__init__();
        if (!$init_res){
            return false;
        }
        if (is_array($init_res)){
            return $init_res;
        }

        //如果只有一片
        if(($this->totalChunk == 1 && $this->currentChunk == 1)){
            $this->writeToFile();
            $this->onFinish(true);
            return $this->getResult();
        }else{
            $this->writeChunk();
        }

        //最后一片
        if ($this->currentChunk == $this->totalChunk){
            //所有分片都完成了，更新数据库信息
            $this->onFinish();
            return $this->getResult();
        }

        return $this->getMessage(2222);
    }

    private function onFinish($only_one = false){
        //如果是图片,生成缩略图
        $image = new ThumpImageHandler();
        $image->setSrc($this->url);
        if($image->isImg()){
            $image->saveImage($this->thumpPath);
            $image->destructImage();
        }else{
            $this->thump_url = "";
        }

        //只要一片不写数据库
        if (!$only_one){
            $data = [
                "path" => $this->filePathComplete,
                "url" => $this->url,
                "thump_url" => $this->thump_url,
                "is_finish" => 1,
                "chunk" => $this->currentChunk,
            ];
            \Yii::$app->db->createCommand()->update($this->table,$data,["unique_id" => $this->unique_id])->execute();
        }
    }


    private function writeToFile($content = null){
        if (!$content){
            $content = file_get_contents($this->temp_file);
        }
        file_put_contents($this->filePathComplete,$content,FILE_APPEND|LOCK_EX);
        return true;
    }

    /**
     * 写入切片
     */
    private function writeChunk(){
        //最后一片直接写入临时文件
        if ($this->currentChunk == $this->totalChunk){
            //写入上一片
            $this->writeChunkToTempFile();
            //直接写入当前片到临时文件
            file_put_contents($this->fileTempPath,file_get_contents($this->temp_file),FILE_APPEND|LOCK_EX);
            //移动文件
            $this->mvFile($this->fileTempPath,$this->filePathComplete);
        }else{
            file_put_contents($this->chunkTempPath,file_get_contents($this->temp_file),LOCK_EX);
            //不是第一片
            if ($this->currentChunk > 1){
                $this->writeChunkToTempFile();
            }
        }
    }

    /**
     * 将上一个切片写入临时文件
     */
    private function writeChunkToTempFile(){
        if (file_exists($this->lastChunkTempPath)){
            file_put_contents($this->fileTempPath,file_get_contents($this->lastChunkTempPath),FILE_APPEND|LOCK_EX);
            $this->delFile($this->lastChunkTempPath);
            if ($this->info){
                \Yii::$app->{$this->db}->createCommand()->update($this->table,["chunk" => ($this->currentChunk-1)],["unique_id" => $this->unique_id])->execute();
            }else{
                $data = [
                    "unique_id" => $this->unique_id,
                    "file_name" => $this->fileName,
                    "size" => $this->size,
                    "type" => $this->type,
                    "chunk_num" => $this->totalChunk,
                    "chunk_size" => $this->chunkSize,
                    "chunk" => ($this->currentChunk-1),
                ];
                \Yii::$app->{$this->db}->createCommand()->insert($this->table,$data)->execute();
            }
        }
    }

    public function getResult(){
        return [
            "ok" => true,
            "code" => 200,
            "msg" => "上传成功",
            "url" => $this->url,
            "thump_url" => $this->thump_url,
            "basePath" => $this->basePath,
            "fileName" => $this->fileName,
            "size" => $this->size,
            "unique_id" => $this->unique_id
        ];
    }

    protected  function create_dir($dirName){
        // 去除输入目录名中的空格部分
        $dirName = trim($dirName);

        // 判断输入的目录名称不能为空
        if (empty($dirName)) {
            return false;
        } else {
            // 判断是否存在相同文件或目录
            if (file_exists($dirName)) {
                return true;
            } else {
                // 判断并创建目录
                if (mkdir($dirName, 0777, true)) {
                    return true;
                } else {
                    return false;
                }
            }
        }
    }

    /**
     * @param $path
     * 删除文件
     */
    protected function delFile($path){
        if (file_exists($path)){
            $url=iconv('utf-8','gbk',$path);
            if(PATH_SEPARATOR == ':'){ //linux
                unlink($path);
            }else{  //Windows
                unlink($url);
            }
        }
    }

    /**
     * @param $from
     * @param $to
     * 移动文件
     */
    protected function mvFile($from,$to){
        if (file_exists($from) && !file_exists($to)){
            copy($from,$to); //拷贝到新目录
            unlink($from); //删除旧目录下的文件
        }
    }

}
