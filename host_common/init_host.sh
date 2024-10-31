# ホストマシンの初期化
# 実際に直接このスクリプトを実行することはおそらくないが目安として記述
sudo apt update

# mysqlインストール
sudo apt install mariadb-server
# TODO:
# https://www.digitalocean.com/community/tutorials/how-to-install-mariadb-on-ubuntu-20-04-ja
# mysqlリモートユーザーの作成..

# TODO: dockerインストール
# https://www.digitalocean.com/community/tutorials/how-to-install-and-use-docker-on-ubuntu-20-04-ja

# その他必要なディレクトリ、ファイルの作成
sudo mkdir /srv/hapi /srv/hapi_dev /srv/traefik -p

# github actionsで使用するユーザーの作成
useradd -G docker -m github-actions
chown -R github-actions:github-actions /srv/* -R

# 公開sshキー、ホストキーの設定
# iptables.v4ファイルの適用
sudo apt install iptables-persistent

# host-***/app/ ディレクトリ内の各ファイルを該当サーバーにコピー
# 非同期レプリケーション設定をマスター側、スレーブ側でそれぞれ行う
