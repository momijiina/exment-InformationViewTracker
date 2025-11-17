<?php

namespace App\Plugins\InformationViewTracker;

use Exceedone\Exment\Services\Plugin\PluginEventBase;
use Exceedone\Exment\Model\CustomTable;
use Carbon\Carbon;

class PluginEvent extends PluginEventBase
{
    /**
     * イベント実行
     * お知らせの詳細画面表示時（loading イベント）に閲覧記録を保存
     */
    public function execute()
    {
        try {
            // 現在のユーザーを取得
            $user = \Exment::user();
            if (!$user) {
                \Log::warning('InformationViewTracker: ユーザーが取得できませんでした');
                return true;
            }

            // カスタム値（お知らせ情報）を取得
            $informationId = $this->custom_value->id ?? null;
            if (!$informationId) {
                \Log::warning('InformationViewTracker: お知らせIDが取得できませんでした');
                return true;
            }

            // information_viewsカスタムテーブルを取得
            $viewsTable = CustomTable::getEloquent('information_views');
            if (!$viewsTable) {
                \Log::warning('InformationViewTracker: information_viewsテーブルが見つかりません。先にカスタムテーブルを作成してください。');
                return true;
            }

            $userId = $user->base_user_id;
            $now = Carbon::now();

            // 既存の閲覧記録をチェック
            $existingView = $viewsTable->getValueModel()
                ->where('value->information_id', $informationId)
                ->where('value->user_id', $userId)
                ->first();

            if ($existingView) {
                // 既に閲覧記録がある場合は更新（最終閲覧日時と閲覧回数を更新）
                $viewCount = intval($existingView->getValue('view_count', 0)) + 1;
                $existingView->setValue('last_viewed_at', $now);
                $existingView->setValue('view_count', $viewCount);
                $existingView->save();
                
                \Log::info("InformationViewTracker: 閲覧記録を更新しました。お知らせID: {$informationId}, ユーザーID: {$userId}, 閲覧回数: {$viewCount}");
            } else {
                // 新規閲覧記録を作成
                $newView = $viewsTable->getValueModel();
                $newView->setValue('information_id', $informationId);
                $newView->setValue('user_id', $userId);
                $newView->setValue('first_viewed_at', $now);
                $newView->setValue('last_viewed_at', $now);
                $newView->setValue('view_count', 1);
                $newView->save();
                
                \Log::info("InformationViewTracker: 閲覧記録を作成しました。お知らせID: {$informationId}, ユーザーID: {$userId}");
            }

            return true;
        } catch (\Exception $e) {
            \Log::error('InformationViewTracker: エラーが発生しました: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            // エラーが発生してもExmentの動作は継続
            return true;
        }
    }
}
