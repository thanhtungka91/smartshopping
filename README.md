# SmartShopping Application 
It is simple application for crawler products from amz with jan number 

## Installation

1. Clone app 
```
git clone https://github.com/thanhtungka91/smartshopping.git
```
2. Run app (if you have composer, please install  php, composer before)
```
brew search intl
brew install homebrew/php/php71-intl
```
Then install cakephp and libraies 
```
composer install 
```

3. Start App 

```bash
bin/cake server -p 8765
```

Then visit `http://localhost:8765` to see the welcome page.

## Configuration
1. Configue database 
Read and edit `config/app.php` and setup the `'Datasources'` and any other
configuration relevant for your application.

2. Create Database 
a. Create database, please note that need to set utf8 which is used for saving japanese charater latter. 
```
CREATE DATABASE smartshopping
  DEFAULT CHARACTER SET utf8
  DEFAULT COLLATE utf8_general_ci;
```
b. Create products table
```
CREATE TABLE `smartshopping`.`products` (
  `product_id` INT NOT NULL AUTO_INCREMENT,
  `product_name` VARCHAR(45) NULL,
  `product_jan` VARCHAR(45) NULL,
  `product_asin` VARCHAR(45) NULL,
  `product_amz_url` VARCHAR(255) NULL,
  PRIMARY KEY (`product_id`));
```

## How to use the application 
1. Copy file jan to smartshopping directory 
example file 
```
4904740511511
4973512257896
```
2. Run crawler 
http://localhost:8765/crawler
