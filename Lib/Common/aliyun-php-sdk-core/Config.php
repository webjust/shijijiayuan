<?php
/*
 * Licensed to the Apache Software Foundation (ASF) under one
 * or more contributor license agreements.  See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership.  The ASF licenses this file
 * to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License.  You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied.  See the License for the
 * specific language governing permissions and limitations
 * under the License.
 */
include_once 'Autoloader/Autoloader.php';
include_once 'Regions/EndpointConfig.php';


//config sdk auto load path.
Autoloader::addAutoloadPath("aliyun-php-sdk-sms");
//Autoloader::addAutoloadPath("aliyun-php-sdk-ecs");
//Autoloader::addAutoloadPath("aliyun-php-sdk-batchcompute");
//Autoloader::addAutoloadPath("aliyun-php-sdk-sts");
//Autoloader::addAutoloadPath("aliyun-php-sdk-push");
//Autoloader::addAutoloadPath("aliyun-php-sdk-ram");
//Autoloader::addAutoloadPath("aliyun-php-sdk-ubsms");
//Autoloader::addAutoloadPath("aliyun-php-sdk-ubsms-inner");
//Autoloader::addAutoloadPath("aliyun-php-sdk-green");
use Sms\Request\V20160927 as Sms;
//config http proxy	
define('ENABLE_HTTP_PROXY', FALSE);
define('HTTP_PROXY_IP', '127.0.0.1');
define('HTTP_PROXY_PORT', '8888');

$iClientProfile = DefaultProfile::getProfile("cn-hangzhou", "LTAI2KwIFjhDL0j1", "KOeSgtZ1XaJxqTFgUliD0qmFxFSogX");        
$client = new DefaultAcsClient($iClientProfile);    
$request = new Sms\SingleSendSmsRequest();
$request->setSignName("彩妆国季");/*签名名称*/
$request->setTemplateCode($TemplateCode);/*模板code*/
$request->setRecNum($RecNum);/*目标手机号*/
$request->setParamString($ParamString);/*模板变量，数字一定要转换为字符串*/