# Yii2 超大文件上传组件(支持断点续传)

## 1、安装

```text
composer: composer require huangdingbo/yii2-uploader

git: https://github.com/huangdingbo/yii2-uploader
```

## 2、使用

#### 1、创建数据表

将 sys_upload_files.sql 导入数据库中

#### 2、配置组件

在main.php的components中加入:
```php
       'uploader' => [
            'class' => 'uploader\FileUploaderComponent', 
            'allowExt' => '*', //运行的文件扩展名, "*" || ["*"] "" ["png","jpg"], * 表示不限制
            'basePath' => '@webroot/static/uploader', //上传文件的根路径
            'temp_path' => '@webroot/static/temp', //临时文件的存放路径
            'chunk_path' => '@webroot/static/chunks', //切片的临时存放路径
            'web_path' => '@web/static/uploader', //文件上传后的访问路径
            'db' => 'db', //数据库连接组件
            'table' => 'sys_upload_files' //数据表
       ]
```

#### 3、实例

后端DEMO：
```php
        $fileInfo = \Yii::$app->request->post();
        $fileName = ArrayHelper::getValue($fileInfo,'fileName');
        $size = ArrayHelper::getValue($fileInfo,'size');
        $type = ArrayHelper::getValue($fileInfo,'type');
        $chunkNum = ArrayHelper::getValue($fileInfo,'chunkNum');
        $chunk = ArrayHelper::getValue($fileInfo,'chunk');
        $chunkSize = ArrayHelper::getValue($fileInfo,'chunkSize');

        if(!$fileName || !$size || !$type || !$chunkNum || !$chunk || !$chunkSize || !$_FILES["file"]["tmp_name"]){
            return ["ok" => false,"code" => 201,"msg" => "参数不完整"];
        }
        
        //实例化组件
        $handler = \Yii::$app->uploader; 
        //设置参数
        $handler->setFileName($fileName)->setSize($size)->setType($type)->setTotalChunk($chunkNum)->setCurrentChunk($chunk)->setChunkSize($chunkSize);
        //执行
        $result = $handler->execute();

        if (is_array($result)){
            return $result;
        }else{
           return $handler->getError()[0];
        }
```

前端DEMO:

```vue
</template>
    <input type="file" value="断点续传" @change="getFileDemo($event)">
</template>
<script>
export default {
    data(){
        return {
             chunkSize: 1024 * 1024, //切片的大小
             data:[], //文件信息
             currentTrunk:0, //当前上传分片的索引
             index:0, //服务器存在分片的索引
        }            
    },
     methods:{
     sendFileDemo(){
                     let start = this.currentTrunk * this.chunkSize;
     
                     let end = 0;
                     if(this.currentTrunk+1 === this.data.chunkNum){
                         end = this.data.len
                     }else {
                         end = start + this.chunkSize;
                     }
                     let blob = this.data.file.slice(start,end);
                     let formData = new FormData();
                     formData.append('file', blob);
                     formData.append('fileName', this.data.name);
                     formData.append('size', this.data.len);
                     formData.append('type', this.data.type);
                     formData.append('chunkNum', this.data.chunkNum);
                     formData.append('chunk', (this.currentTrunk+1));
                     formData.append('chunkSize', this.chunkSize);
                     let config = {
                         headers: {
                             'Content-Type': 'multipart/form-data',
                         }
                     };
                     this.axios.post("http://192.168.1.30:9527/CYApi/test/test/index",formData,config).then(res=>{
                         if (res.ok){
                             if (this.currentTrunk+1 === this.data.chunkNum){
                                 console.log("上传完成:",res)
                             }else {
                                 this.currentTrunk++;
                                 this.sendFileDemo();
                                 console.log(res)
                             }
                         }
                         console.log(this.currentTrunk)
                     })
     
                 },
     getFileDemo(event){
              let _file = event.target.files[0];
              this.data = {
              "file" : event.target.files[0],
              "len" :  _file.size,
              "name" : _file.name,
              "type" : _file.type,
              "chunkNum" :  Math.ceil(_file.size/this.chunkSize)
             };
                console.log(this.data);
                this.sendFileDemo();
     },
     }
}
</script>
```

## 返回状态码说明

```php
 [
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
```
