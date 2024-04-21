# Laravel OpenAI

## Introduction

## Prerequisites

## Installation

1. 若未下載 Docker Desktop 或是 [OrbStack](https://orbstack.dev/)（建議）者，需先下載。

2. 先確認有沒有任何程序佔用 80 port（或是 Docker 要使用的 port 號），若有，需先停止。若需停止 Apache ，請執行以下 command ：
```
sudo apachectl -k stop
```

3. 將 fork 的專案 clone 至本地，請執行以下 command：
( `Path` 為欲放專案的本地路徑， `Username` 為個人 GitHub 帳號， `Your Name` 為專案名稱後綴，請自行替換)
```
cd {Path}
```
```
git clone https://github.com/{Username}/BECamp_T13_HW2_Laravel-AI_{Your Name}
```

4.將專案中的 .env.example 複製一份在專案中，並將檔名改為 .env ，檢查 `DB_USERNAME` 、 `DB_PASSWORD` 是否跟專案所使用的設定一致， `OPENAI_API_KEY` 輸入有效 API key，輸入後儲存。

5.請執行以下 command ，安裝專案所需相關套件並啟動開發環境：
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


## 作業目標

## Usage
- 請 Fork 一份到 `Goodidea-backend-camp` 這個 Organization，名稱取叫 `BECamp_T13_HW2_Laravel-AI_{Your Name}`，例如 `BECamp_T13_HW2_Laravel-AI_JYu`。
- 根據每個功能開 branch，發 PR 到自己的 main 分支。
- 發 PR 前請先確認 CI 流程有通過。
- 主專案不定時會進行調整，請盡量保持與主專案最新狀態。
- 本專案有使用 [Laravel Sail](https://laravel.com/docs/11.x/sail)，自己斟酌要不要使用。

## Working Flow