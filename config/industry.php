<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

//行业ID映射
$industry = array(
    //1 => "宏观",
    //2 => "证券",
    3 => "银行",
    4 => "保险",
    5 => "基金",
    6 => "私募",
    7 => "理财",
    8 => "黄金",
    9 => "外汇",
    10 => "中概股",
    11 => "港股",
    12 => "汽车",
    13 => "新能源",
    14 => "房产",
    15 => "期货",
    16 => "信托",
    17 => "上市公司",
    18 => "国际财经",
    19 => "传媒",
    20 => "酒行业",
    21 => "医药",
    22 => "农业",
    23 => "煤炭",
    24 => "电力",
    25 => "家电",
    26 => "VcPE",
    27 => "环保",
    28 => "交通运输",
    29 => "军工",
    30 => "机械",
    31 => "化工",
    32 => "全行业机动",    
    //test
    1 => "TMT",
    2 => "财经",
);

$config['industry'] = $industry;