<div align="center">

# Laravel OpenAI

![Ubuntu Badge](https://img.shields.io/badge/Ubuntu-E95420?style=for-the-badge&logo=ubuntu&logoColor=white) 
![PHP Badge](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white) 
![Laravel Badge](https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white) 
![MySQL Badge](https://img.shields.io/badge/MySQL-005C84?style=for-the-badge&logo=mysql&logoColor=white) 
![Postman Badge](https://img.shields.io/badge/Postman-FF6C37?style=for-the-badge&logo=Postman&logoColor=white) 
![Git Badge](https://img.shields.io/badge/GIT-E44C30?style=for-the-badge&logo=git&logoColor=white) 
![GitHub Badge](https://img.shields.io/badge/GitHub-100000?style=for-the-badge&logo=github&logoColor=white)

</div>

## 專案簡介
此專案是使用 **Laravel** 框架開發的應用程式，整合了 **MySQL** 資料庫與 **OpenAI API**，並使用 **Postman** 進行測試。
使用者須先註冊後，可使用 AI 進行聊天，並生成相對應的圖片。

## Demo
https://github.com/user-attachments/assets/796fd3e3-471a-4a48-b630-cbcde8852aed

## 資料表
![資料表圖片](https://github.com/Goodidea-backend-camp/BECamp_T13_HW2_Laravel-AI_fufuYang/blob/development/public/images/%E8%B3%87%E6%96%99%E8%A1%A8.png)


## 安裝與設定

1. 若未下載 Docker Desktop 或是 [OrbStack](https://orbstack.dev/)（建議）者，需先下載。

2. 先確認有沒有任何程序佔用 80 port（或是 Docker 要使用的 port 號），若有，需先停止。

3. 將 fork 的專案 clone 至本地，請執行以下 command：
( `Path` 為欲放專案的本地路徑， `Username` 為個人 GitHub 帳號， `Your Name` 為專案名稱後綴，請自行替換)
```
cd {Path}
```
```
git clone https://github.com/{Username}/BECamp_T13_HW2_Laravel-AI_{Your Name}
```

4. 將專案中的 .env.example 複製一份在專案中，並將檔名改為 .env ，完成後儲存。

5. 請執行以下 command ，安裝專案所需相關套件並啟動開發環境：
```
composer install
```
```
./vendor/bin/sail up -d
```
```
./vendor/bin/sail artisan key:generate
```
```
./vendor/bin/sail artisan migrate
```


## 專案功能
![功能圖](https://github.com/Goodidea-backend-camp/BECamp_T13_HW2_Laravel-AI_fufuYang/blob/development/public/images/Ai-chat-chat.png)





