# gh workflow run コマンドのユーティリティ

# プッシュ
# 	gh workflow run hapi-push --ref main --field dev=true
# 	gh workflow run hapi-push --ref main --field dev=false

# デプロイ
# 	gh workflow run hapi-deploy --ref main --field mode=dev-dev --server=fo		// 開発部用。FOサーバー
# 	gh workflow run hapi-deploy --ref main --field mode=dev-prod --server=main	// 開発部、社内テスト用。メインサーバー
# 	gh workflow run hapi-deploy --ref main --field mode=prod-prod --server=main&fo	// 本番用用。メイン、FOサーバー両方
# 	※さらにメインサーバーかFOサーバーかの選択

# まとめると
# ghw-run push --dev=true [commit]	// commitはmainがデフォ
# ghw-run push --dev=false [commit]	
# ghw-run deploy --mode=dev-dev [commit]
# ghw-run deploy --mode=dev-prod [commit]
# ghw-run deploy --mode=prod-prod [commit]
