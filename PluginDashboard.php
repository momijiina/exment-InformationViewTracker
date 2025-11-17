<?php

namespace App\Plugins\InformationViewTracker;

use Exceedone\Exment\Services\Plugin\PluginDashboardBase;
use Exceedone\Exment\Model\CustomTable;
use Carbon\Carbon;

class PluginDashboard extends PluginDashboardBase
{
    /**
     * ダッシュボードの本体を返す
     */
    public function body()
    {
        // 現在のユーザーを取得
        $user = \Exment::user();
        if (!$user) {
            return $this->generateErrorHtml('ユーザー情報を取得できませんでした');
        }
        
        $userId = $user->base_user_id;
        
        try {
            // informationテーブルの存在確認
            $informationTable = CustomTable::getEloquent('information');
            if (!$informationTable) {
                return $this->generateErrorHtml('お知らせテーブルが見つかりません');
            }

            // 統計情報を取得
            $stats = $this->getStatistics($userId);
            
            // HTML生成
            return $this->generateDashboardHtml($stats);
            
        } catch (\Exception $e) {
            \Log::error('InformationViewTracker Dashboard Error: ' . $e->getMessage());
            return $this->generateErrorHtml('ダッシュボードの表示中にエラーが発生しました');
        }
    }

    /**
     * 統計情報を取得
     */
    protected function getStatistics($userId)
    {
        // informationテーブルを取得
        $informationTable = CustomTable::getEloquent('information');
        
        // 全お知らせ件数
        $totalInformation = $informationTable ? $informationTable->getValueModel()->count() : 0;
        
        // information_viewsカスタムテーブルを取得
        $viewsTable = CustomTable::getEloquent('information_views');
        $readCount = 0;
        $recentViews = collect();
        
        if ($viewsTable) {
            // 既読お知らせ件数（このユーザーの閲覧記録数）
            $readCount = $viewsTable->getValueModel()
                ->where('value->user_id', $userId)
                ->count();
            
            // 最近閲覧したお知らせ（最大5件）
            $viewRecords = $viewsTable->getValueModel()
                ->where('value->user_id', $userId)
                ->orderBy('updated_at', 'desc')
                ->limit(5)
                ->get();
            
            // お知らせの詳細情報を取得
            foreach ($viewRecords as $viewRecord) {
                $infoId = $viewRecord->getValue('information_id');
                if ($infoId && $informationTable) {
                    $info = $informationTable->getValueModel()->find($infoId);
                    if ($info) {
                        $recentViews->push((object)[
                            'id' => $infoId,
                            'value' => json_encode($info->value ?? []),
                            'last_viewed_at' => $viewRecord->getValue('last_viewed_at'),
                            'view_count' => $viewRecord->getValue('view_count', 0)
                        ]);
                    }
                }
            }
        }
        
        // 未読お知らせ件数
        $unreadCount = $totalInformation - $readCount;
        
        // 今日の新着お知らせ
        $todayNew = 0;
        if ($informationTable) {
            $todayNew = $informationTable->getValueModel()
                ->whereDate('created_at', Carbon::today())
                ->count();
        }
        
        return [
            'total' => $totalInformation,
            'read' => $readCount,
            'unread' => $unreadCount,
            'today_new' => $todayNew,
            'recent_views' => $recentViews,
            'read_percentage' => $totalInformation > 0 ? round(($readCount / $totalInformation) * 100, 1) : 0
        ];
    }

    /**
     * ダッシュボードHTMLを生成
     */
    protected function generateDashboardHtml($stats)
    {
        $readPercentage = $stats['read_percentage'];
        $progressColor = $readPercentage >= 80 ? '#28a745' : ($readPercentage >= 50 ? '#ffc107' : '#dc3545');
        
        $html = '
        <div style="
            background: #fff; 
            padding: 0; 
            border-radius: 3px; 
            box-shadow: 0 1px 1px rgba(0,0,0,0.1); 
            margin: 0;
            font-family: Arial, sans-serif;
        ">
            <!-- ヘッダー -->
            <div style="
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 20px;
                border-radius: 3px 3px 0 0;
            ">
                <h3 style="margin: 0; font-size: 20px; font-weight: bold;">
                    📊 お知らせ閲覧状況
                </h3>
                <p style="margin: 5px 0 0 0; opacity: 0.9; font-size: 13px;">
                    あなたの閲覧統計
                </p>
            </div>

            <!-- 統計カード -->
            <div style="padding: 20px;">
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 20px;">
                    <!-- 全体 -->
                    <div style="
                        background: #f8f9fa;
                        padding: 15px;
                        border-radius: 5px;
                        text-align: center;
                        border-left: 4px solid #6c757d;
                    ">
                        <div style="font-size: 28px; font-weight: bold; color: #6c757d;">
                            ' . $stats['total'] . '
                        </div>
                        <div style="font-size: 12px; color: #666; margin-top: 5px;">
                            全お知らせ
                        </div>
                    </div>

                    <!-- 既読 -->
                    <div style="
                        background: #d4edda;
                        padding: 15px;
                        border-radius: 5px;
                        text-align: center;
                        border-left: 4px solid #28a745;
                    ">
                        <div style="font-size: 28px; font-weight: bold; color: #28a745;">
                            ' . $stats['read'] . '
                        </div>
                        <div style="font-size: 12px; color: #155724; margin-top: 5px;">
                            既読
                        </div>
                    </div>

                    <!-- 未読 -->
                    <div style="
                        background: #f8d7da;
                        padding: 15px;
                        border-radius: 5px;
                        text-align: center;
                        border-left: 4px solid #dc3545;
                    ">
                        <div style="font-size: 28px; font-weight: bold; color: #dc3545;">
                            ' . $stats['unread'] . '
                        </div>
                        <div style="font-size: 12px; color: #721c24; margin-top: 5px;">
                            未読
                        </div>
                    </div>

                    <!-- 今日の新着 -->
                    <div style="
                        background: #d1ecf1;
                        padding: 15px;
                        border-radius: 5px;
                        text-align: center;
                        border-left: 4px solid #17a2b8;
                    ">
                        <div style="font-size: 28px; font-weight: bold; color: #17a2b8;">
                            ' . $stats['today_new'] . '
                        </div>
                        <div style="font-size: 12px; color: #0c5460; margin-top: 5px;">
                            今日の新着
                        </div>
                    </div>
                </div>

                <!-- 進捗バー -->
                <div style="margin-bottom: 25px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span style="font-size: 14px; color: #333; font-weight: bold;">閲覧進捗率</span>
                        <span style="font-size: 14px; color: ' . $progressColor . '; font-weight: bold;">' . $readPercentage . '%</span>
                    </div>
                    <div style="
                        background: #e9ecef;
                        height: 24px;
                        border-radius: 12px;
                        overflow: hidden;
                        box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
                    ">
                        <div style="
                            background: linear-gradient(90deg, ' . $progressColor . ' 0%, ' . $progressColor . 'dd 100%);
                            height: 100%;
                            width: ' . $readPercentage . '%;
                            transition: width 0.3s ease;
                            display: flex;
                            align-items: center;
                            justify-content: flex-end;
                            padding-right: 10px;
                        ">
                            <span style="color: white; font-size: 11px; font-weight: bold;">
                                ' . $stats['read'] . ' / ' . $stats['total'] . '
                            </span>
                        </div>
                    </div>
                </div>

                <!-- 最近閲覧したお知らせ -->
                <div>
                    <h4 style="
                        font-size: 16px;
                        color: #333;
                        margin: 0 0 12px 0;
                        padding-bottom: 8px;
                        border-bottom: 2px solid #e9ecef;
                        font-weight: bold;
                    ">
                        📖 最近閲覧したお知らせ
                    </h4>';
        
        if ($stats['recent_views']->isEmpty()) {
            $html .= '
                    <div style="
                        text-align: center;
                        padding: 30px;
                        color: #999;
                        background: #f8f9fa;
                        border-radius: 5px;
                    ">
                        <div style="font-size: 48px; margin-bottom: 10px;">📭</div>
                        <div>まだ閲覧履歴がありません</div>
                    </div>';
        } else {
            $html .= '<div style="background: #f8f9fa; border-radius: 5px; padding: 10px;">';
            
            foreach ($stats['recent_views'] as $view) {
                $valueData = json_decode($view->value, true);
                $title = $valueData['title'] ?? '(タイトルなし)';
                $lastViewed = Carbon::parse($view->last_viewed_at)->format('Y/m/d H:i');
                $viewCount = $view->view_count;
                $infoId = $view->id;
                
                $html .= '
                        <div style="
                            background: white;
                            padding: 12px;
                            margin-bottom: 8px;
                            border-radius: 4px;
                            border-left: 3px solid #667eea;
                            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
                        ">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div style="flex: 1;">
                                    <a href="/admin/data/information/' . $infoId . '" style="
                                        color: #333;
                                        text-decoration: none;
                                        font-weight: 500;
                                        font-size: 14px;
                                    " onmouseover="this.style.color=\'#667eea\'" onmouseout="this.style.color=\'#333\'">
                                        ' . htmlspecialchars($title) . '
                                    </a>
                                    <div style="font-size: 11px; color: #999; margin-top: 4px;">
                                        最終閲覧: ' . $lastViewed . ' | 閲覧回数: ' . $viewCount . '回
                                    </div>
                                </div>
                            </div>
                        </div>';
            }
            
            $html .= '</div>';
        }
        
        $html .= '
                </div>
            </div>

            <!-- フッター -->
            <div style="
                background: #f8f9fa;
                padding: 12px 20px;
                border-radius: 0 0 3px 3px;
                text-align: center;
                border-top: 1px solid #e9ecef;
            ">
                <a href="/admin/data/information" style="
                    color: #667eea;
                    text-decoration: none;
                    font-size: 13px;
                    font-weight: 500;
                " onmouseover="this.style.textDecoration=\'underline\'" onmouseout="this.style.textDecoration=\'none\'">
                    📋 お知らせ一覧を見る →
                </a>
            </div>
        </div>';
        
        return $html;
    }

    /**
     * エラー表示用HTML
     */
    protected function generateErrorHtml($message)
    {
        return '
        <div style="
            background: #fff;
            padding: 20px;
            border-radius: 3px;
            box-shadow: 0 1px 1px rgba(0,0,0,0.1);
            margin: 0;
            text-align: center;
            color: #dc3545;
        ">
            <div style="font-size: 48px; margin-bottom: 15px;">⚠️</div>
            <div style="font-size: 16px; font-weight: bold; margin-bottom: 8px;">
                エラー
            </div>
            <div style="font-size: 14px; color: #666;">
                ' . htmlspecialchars($message) . '
            </div>
        </div>';
    }
}
