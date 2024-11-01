#!/bin/bash
# gh workflow run コマンドのユーティリティ

push() {
    workflow=
    dev=false
    while [["$1" != ""]]; do
        case "$1" in
            --target=*)
                target="${1#*=}"
                if [[ "$target" = "hapi" ]]; then
                    workflow=hapi-push.yml
                else
                    echo "Error: Invalid target."
                    return 1
                fi
                ;;
            --dev)
                dev=true
                ;;
            *)
                echo "Invalid option: $1"
                return 1
                ;;
        esac
        shift
    done

    gh workflow run $workflow --field dev=$dev
    $target
}

deploy() {
    workflow=
    devenv=false
    devimg=false
    fo_server=false

    while [["$1" != ""]]; do
        case "$1" in
            --target=*)
                target="${1#*=}"
                if [[ "$target" = "hapi" ]]; then
                    workflow=hapi-deploy.yml
                elif [[ "$target" = "traefik" ]]; then
                    workflow=traefik-deploy.yml
                else
                    echo "Error: Invalid target."
                    return 1
                fi
                ;;
            # TODO: 残りの引数..
            *)
                echo "Invalid option: $1"
                return 1
                ;;
        esac
        shift
    done

    # TODO: 最終的なコマンド実行..
}

case "$1" in
    push)
        push
        ;;
    deploy)
        deploy
        ;;
    *)
        echo "Usage: $0 {push|deploy}"
        exit 1
        ;;
esac

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
