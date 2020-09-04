-- phpMyAdmin SQL Dump
-- version 5.0.2
-- https://www.phpmyadmin.net/
--
-- 主机： localhost
-- 生成日期： 2020-09-04 06:49:08
-- 服务器版本： 5.7.26-log
-- PHP 版本： 7.2.24

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `advanced`
--

-- --------------------------------------------------------

--
-- 表的结构 `sys_upload_files`
--

DROP TABLE IF EXISTS `sys_upload_files`;
CREATE TABLE `sys_upload_files` (
  `id` int(11) NOT NULL,
  `unique_id` varchar(64) NOT NULL COMMENT '唯一键',
  `file_name` varchar(64) NOT NULL COMMENT '文件名',
  `size` int(11) NOT NULL COMMENT '文件大小',
  `type` varchar(32) NOT NULL COMMENT '文件类型',
  `chunk_num` int(11) NOT NULL COMMENT '分片总数',
  `chunk_size` int(11) NOT NULL COMMENT '每片大小',
  `chunk` int(11) NOT NULL COMMENT '当前已成功写入的分片',
  `path` varchar(256) DEFAULT NULL COMMENT '服务器文件存储路径',
  `url` varchar(256) DEFAULT NULL COMMENT '访问地址',
  `thump_url` varchar(256) DEFAULT NULL COMMENT '缩略图地址',
  `is_finish` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否完成',
  `update_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后更新时间'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- 转储表的索引
--

--
-- 表的索引 `sys_upload_files`
--
ALTER TABLE `sys_upload_files`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_idx_id` (`unique_id`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `sys_upload_files`
--
ALTER TABLE `sys_upload_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
