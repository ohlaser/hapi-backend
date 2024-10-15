# ホストマシンの初期化
sudo apt update

# mysqlインストール
sudo apt install mariadb-server
# TODO:
# https://www.digitalocean.com/community/tutorials/how-to-install-mariadb-on-ubuntu-20-04-ja

# TODO: dockerインストール
# https://www.digitalocean.com/community/tutorials/how-to-install-and-use-docker-on-ubuntu-20-04-ja

# その他必要なディレクトリ、ファイルの作成
sudo mkdir /hapi/traefik/data/ -p
sudo touch /hapi/traefik/data/acme.json

# github actionsで使用するユーザーの作成
useradd -G docker -m github-actions
chown -R github-actions:github-actions /hapi

# 公開sshキーの設定..
