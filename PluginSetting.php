<?php

namespace App\Plugins\InformationViewTracker;

use Exceedone\Exment\Services\Plugin\PluginSettingBase;

class PluginSetting extends PluginSettingBase
{
    protected $useCustomOption = false;

    /**
     * カスタムオプションフォームの設定
     * このプラグインでは特に設定項目は不要
     */
    public function setCustomOptionForm(&$form)
    {
        $form->description('このプラグインは自動的にお知らせの閲覧状況を追跡します。');
        $form->html('<div style="background: #e3f2fd; padding: 15px; border-radius: 5px; margin-top: 10px;">
            <h4 style="margin-top: 0; color: #1976d2;">📌 プラグインの機能</h4>
            <ul style="margin-bottom: 0; padding-left: 20px;">
                <li><strong>自動閲覧追跡:</strong> お知らせの詳細画面を開くと自動的に閲覧記録が保存されます</li>
                <li><strong>カスタムビュー:</strong> お知らせ一覧で既読/未読の状態を確認できます</li>
                <li><strong>ダッシュボード:</strong> 閲覧統計情報をダッシュボードに表示します</li>
            </ul>
        </div>');
        
        $form->html('<div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin-top: 15px; border-left: 4px solid #ffc107;">
            <h4 style="margin-top: 0; color: #856404;">⚙️ セットアップ手順</h4>
            <ol style="margin-bottom: 0; padding-left: 20px; line-height: 1.8;">
                <li><strong>カスタムテーブル作成:</strong> 管理者設定 → テーブル → テーブル追加から以下の設定で<code>information_views</code>テーブルを作成してください
                    <ul style="margin-top: 5px; font-size: 13px;">
                        <li>テーブル名: <code>information_views</code></li>
                        <li>表示名: <code>お知らせ閲覧記録</code></li>
                    </ul>
                </li>
                <li><strong>カスタム列追加:</strong> 作成したテーブルに以下の列を追加してください
                    <ul style="margin-top: 5px; font-size: 13px;">
                        <li><code>information_id</code> (整数) - お知らせID</li>
                        <li><code>user_id</code> (整数) - ユーザーID</li>
                        <li><code>first_viewed_at</code> (日時) - 初回閲覧日時</li>
                        <li><code>last_viewed_at</code> (日時) - 最終閲覧日時</li>
                        <li><code>view_count</code> (整数) - 閲覧回数</li>
                    </ul>
                </li>
                <li><strong>プラグインアップロード:</strong> このプラグインをzipでアップロードして有効化してください</li>
                <li><strong>イベント設定:</strong> お知らせテーブルに「イベントプラグイン」として設定してください（トリガー: loading）</li>
                <li><strong>ビュー設定:</strong> お知らせテーブルに新しいビューを作成し、「ビュープラグイン」として設定してください</li>
                <li><strong>ダッシュボード追加:</strong> ダッシュボードに「ダッシュボードプラグイン」として追加してください</li>
            </ol>
        </div>');
        
        $form->html('<div style="background: #e8f5e9; padding: 15px; border-radius: 5px; margin-top: 15px; border-left: 4px solid #4caf50;">
            <h4 style="margin-top: 0; color: #2e7d32;">✅ 動作確認</h4>
            <ol style="margin-bottom: 0; padding-left: 20px;">
                <li>お知らせの詳細画面を開いてみてください</li>
                <li>ダッシュボードで統計情報が表示されることを確認してください</li>
                <li>カスタムビューで既読/未読マークが表示されることを確認してください</li>
            </ol>
        </div>');
        
        $form->html('<div style="background: #f3e5f5; padding: 15px; border-radius: 5px; margin-top: 15px; border-left: 4px solid #9c27b0;">
            <h4 style="margin-top: 0; color: #6a1b9a;">💡 ヒント</h4>
            <ul style="margin-bottom: 0; padding-left: 20px; font-size: 13px;">
                <li>カスタムテーブルの作成方法はExmentの公式ドキュメントを参照してください</li>
                <li>SQLを実行する必要はありません - Exmentの画面操作だけで設定できます</li>
                <li>詳細なセットアップ方法は同梱のREADME.mdを参照してください</li>
            </ul>
        </div>');
    }
}
