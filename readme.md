
Oh-Laserで展開するwebサービスを化動させるサーバーについてのモノレポ。  
HARUKAからの通信を受け付けるAPIとしてhapiサーバーを用意する。
そのサーバー全体についてこのリポジトリで管理する。


ホスト構成  
| -- | -- |
| OS | Ubuntu |
| データベース | mysql |
| dockerコンテナ | appコンテナ(hapi), traefik |

ホストマシンにapache等のwebサーバーソフトウェアは不要
デプロイはdockerイメージ単位で外部CI/CDワークフロー(gihub actions)から行う想定。
mysqlのデータは非同期レプリケーションで行う。


<!-- ホスト要件 (廃止)
・クーロンの有効化
・仮想ホストの作成. AllowRewrite ALL 等を実行. コンテナへのポートフォワード設定

・ホストサーバーの任意の場所でこのリポジトリを展開
・composer install
・docker build -t image-name . 
・dockerコンテナ起動 -->
