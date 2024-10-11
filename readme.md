
HARUKAからの通信を受け付けるAPIとしてhapiサーバーを用意する。
そのサーバー全体についてこのリポジトリで管理する。


ホスト要件
| -- | -- |
| OS | Ubuntu |
| データベース | mysql |
| dockerコンテナ | appコンテナ(hapi), traefik |

ホストマシンにapache等のwebサーバーソフトウェアは不要
デプロイはdockerイメージ単位で外部CI/CDワークフローから行う想定。


<!-- ホスト要件 (廃止)
・クーロンの有効化
・仮想ホストの作成. AllowRewrite ALL 等を実行. コンテナへのポートフォワード設定

・ホストサーバーの任意の場所でこのリポジトリを展開
・composer install
・docker build -t image-name . 
・dockerコンテナ起動 -->
