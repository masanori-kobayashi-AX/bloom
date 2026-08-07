# Bloom を Laravel Cloud で公開する手順

サーバ管理不要のマネージド環境（Laravel公式）で公開する手順。小林さん主導で完結できる構成。

- リポジトリ：`dev239pro/bloom`（private）／ブランチ `kobayashi`
- ヘルスチェック：`/up`（Laravel標準・設定済み）
- セッション/キュー：`database`（Redis不要）
- タイムゾーン：Asia/Tokyo、ロケール：ja

## 1. プロジェクト作成
1. https://cloud.laravel.com に GitHub でサインイン
2. New Application → GitHub リポジトリ `dev239pro/bloom` を選択
   - private かつ dev239pro（org）配下のため、GitHub連携で org へのアクセス許可が必要な場合あり
3. ブランチ＝`kobayashi`、リージョン＝**Tokyo (ap-northeast-1)**

## 2. データベース
- MySQL を追加（最小サイズでOK）。接続情報（`DB_*`）は Cloud が自動で環境変数に注入する
- 手で `DB_*` を書く必要はない

## 3. 環境変数（ダッシュボードで設定）
自動設定される `APP_KEY` / `APP_URL` / `DB_*` 以外で、以下を設定：
```
APP_ENV=production
APP_DEBUG=false
APP_TIMEZONE=Asia/Tokyo
APP_LOCALE=ja
SESSION_DRIVER=database
QUEUE_CONNECTION=database
MAIL_MAILER=log        # メール確定まではこれ（実送信なし）
```
メール（SES/SMTP）確定後に差し替え：
```
MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=...   # 検証済み送信元
MAIL_FROM_NAME=Bloom
```

## 4. デプロイコマンド（毎デプロイで実行）
```
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
```
※ アセットビルド不要（本アプリはインラインCSS。`@vite`未使用）

## 5. 初回だけ：初期データ投入
初回デプロイ後、Cloud のコマンド実行/コンソールから **1回だけ**：
```
php artisan db:seed --force
```
→ ロール・店舗・初期アカウント（admin / tencho / kurofuku1 / yui / rin）が入る。
**公開後すぐ初期パスワードを全員変更**（初回ログインで変更フローあり）。

## 6. ドメイン / HTTPS
- Cloud の Domains で独自ドメインを追加 → 表示される値を DNS に設定（CNAME等）
- SSL は自動。`trustProxies` と本番HTTPS強制はコード側で対応済み

## 7. 更新の流れ（日常運用）
- `kobayashi` に push → Cloud が自動でビルド→`migrate --force`→公開
- 問題があれば Cloud のデプロイ履歴から**前のバージョンにロールバック**（ボタン）
- 破壊的なDB変更（NOT NULL追加・型変更等）は事前にスナップショット推奨。既存migrationは書き換えず追加のみ

## 8. 安全対策（任意・推奨）
- **ステージング環境**を別に1つ作り、本番前に確認してから本番反映
- GitHub Actions（`.github/workflows/tests.yml`）でpush時に自動テスト。`kobayashi` にブランチ保護を付け「テスト通過を必須」にすると、壊れたコードの流入を防げる

## 9. 公開直後チェック
- [ ] `/login` 表示・キャスト/黒服/店長ホームが崩れない
- [ ] 初期アカウントのパスワード変更
- [ ] `APP_DEBUG=false`
- [ ] キャスト：顧客登録→保存→再読込で残る
- [ ] 監査ログ・ログイン履歴が記録される
