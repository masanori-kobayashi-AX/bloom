# Bloom デプロイ・運用手順（本番）

対象環境：AWS（EC2 + RDS for MySQL 想定）。PHP 8.2+ / MySQL 8+ / HTTPS 必須 / タイムゾーン Asia/Tokyo。

## 1. 環境変数（`.env`・値はコミットしない）
| 変数 | 用途 |
|---|---|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false`（本番は必ず false） |
| `APP_KEY` | `php artisan key:generate` で生成 |
| `APP_URL` | 本番URL（https） |
| `APP_TIMEZONE` | `Asia/Tokyo` |
| `APP_LOCALE` | `ja` |
| `DB_CONNECTION` | `mysql` |
| `DB_HOST` / `DB_PORT` | RDS エンドポイント / 3306 |
| `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | 本番DB接続情報 |
| `SESSION_DRIVER` | `database`（既定） |

秘密情報は `.env` または AWS Secrets Manager で管理。GitHub にコミットしない。`.env.example` はダミー値のみ。

## 2. 初回デプロイ
```bash
git clone <repo> && cd bloom
composer install --no-dev --optimize-autoloader
cp .env.example .env        # 値を本番用に設定
php artisan key:generate
php artisan migrate --force  # 本番はデプロイ時のみ --force
php artisan db:seed --force  # ロール・店舗・初期アカウント（初回のみ）
php artisan config:cache && php artisan route:cache && php artisan view:cache
```
Web サーバ（Nginx + php-fpm）の公開ディレクトリは `public/`。`storage/` と `bootstrap/cache/` に書き込み権限を付与。

## 3. アップデート時
```bash
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force   # 既存 migration は書き換えない。追加のみ
php artisan config:cache && php artisan route:cache && php artisan view:cache
```
- **既存 migration の書き換え禁止**（差分は必ず新規 migration で追加）。
- NOT NULL 追加・unique 追加・型変更は、既存データで適用可能かを事前確認（段階移行）。

## 4. バックアップ（§14-19）
- **RDS 自動バックアップ**を有効化（保持期間 7 日以上推奨）＋ 手動スナップショットをリリース前に取得。
- 論理バックアップ例：
```bash
mysqldump --single-transaction --routines --triggers \
  -h $DB_HOST -u $DB_USERNAME -p $DB_DATABASE > bloom_$(date +%F).sql
```
- 顧客情報を含むため、バックアップの保管先も暗号化・アクセス制限する。

## 5. 復元試験（本番反映前に必ず一度）
1. ステージング（本番とは別DB）に最新バックアップを復元：
```bash
mysql -h $STG_HOST -u $STG_USER -p $STG_DB < bloom_YYYY-MM-DD.sql
```
2. `php artisan migrate:status` で整合を確認。
3. ログイン・顧客表示・来店登録の主要導線が動くことを確認。
4. 問題なければ本番反映へ。**復元が成功することを確認できて初めてバックアップが有効**。

## 6. ロールバック
- コード：直前のコミット/タグに戻して再デプロイ（`git checkout <tag>` → composer install → cache 再生成）。
- DB：不可逆変更を伴う場合はスナップショットからの復元を優先。`migrate:rollback` は该当バッチのみ・本番では慎重に。

## 7. 本番セキュリティ確認（リリース前チェック）
- [ ] `APP_DEBUG=false` / `APP_ENV=production`
- [ ] HTTPS 強制（HTTP→HTTPS リダイレクト）
- [ ] DB は VPC 内・パブリック非公開、最小権限ユーザー
- [ ] `.env`・秘密鍵が Git 履歴に含まれていない
- [ ] 本番データを開発環境へ無断コピーしない
- [ ] 退店者アカウントの即時停止が機能する（Phase 1）
- [ ] 監査ログ・ログイン履歴が記録される
- [ ] `php artisan test` 全緑

## 8. 稼働確認（スモーク）
- `/login` が表示される／責任者・黒服・キャストの各ホームが崩れない
- キャスト：顧客登録 → 保存 → 再読込で残る
- 黒服：来店開始 → 退店処理
- 責任者：ダッシュボード表示・お知らせ配信
