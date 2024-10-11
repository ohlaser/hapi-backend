# ホストマシンの初期化
sudo apt update

# mysql
sudo apt install mariadb-server
# ...

# TODO: dockerインストール
# ...

# その他必要なディレクトリ、ファイルの作成
sudo mkdir /hapi/
sudo mkdir /hapi/traefik/data/ -p
sudo touch /hapi/traefik/data/acme.json
