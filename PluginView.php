<?php

namespace App\Plugins\InformationViewTracker;

use Exceedone\Exment\Services\Plugin\PluginViewBase;
use Exceedone\Exment\Model\CustomTable;
use Exceedone\Exment\Model\CustomColumn;

class PluginView extends PluginViewBase
{
    /**
     * グリッド表示
     * お知らせ一覧に閲覧状態を追加表示
     */
    public function grid()
    {
        // 現在のユーザー
        $user = \Exment::user();
        if (!$user) {
            return view('exment::errors.500', ['error_message' => 'ユーザー情報を取得できませんでした。']);
        }
        $userId = $user->base_user_id;

        // カスタムビューに基づいたクエリを取得
        $query = $this->custom_table->getValueQuery();
        
        // ビューのフィルタとソートを適用
        $this->custom_view->filterModel($query);
        $this->custom_view->sortModel($query);

        // データ取得（chunkを使用して全データを取得）
        $items = collect();
        $query->chunk(1000, function($values) use(&$items) {
            $items = $items->merge($values);
        });

        // 各お知らせの閲覧状況を取得
        $informationIds = $items->pluck('id')->toArray();
        $viewedMap = [];
        
        if (!empty($informationIds)) {
            // information_viewsカスタムテーブルから閲覧記録を取得
            $viewsTable = CustomTable::getEloquent('information_views');
            if ($viewsTable) {
                $views = $viewsTable->getValueModel()
                    ->where('value->user_id', $userId)
                    ->get();
                
                foreach ($views as $view) {
                    $infoId = $view->getValue('information_id');
                    if (in_array($infoId, $informationIds)) {
                        $viewedMap[$infoId] = $view;
                    }
                }
            }
        }

        // 表示用データを準備
        $displayItems = [];
        foreach ($items as $item) {
            $informationId = $item->id;
            $isViewed = isset($viewedMap[$informationId]);
            
            $displayItems[] = [
                'id' => $informationId,
                'title' => $item->getValue('title') ?? '(タイトルなし)',
                'is_viewed' => $isViewed,
                'last_viewed_at' => $isViewed ? \Carbon\Carbon::parse($viewedMap[$informationId]->getValue('last_viewed_at'))->format('Y/m/d H:i') : null,
                'view_count' => $isViewed ? $viewedMap[$informationId]->getValue('view_count', 0) : 0,
                'start_datetime' => $item->getValue('start_datetime') ? \Carbon\Carbon::parse($item->getValue('start_datetime'))->format('Y/m/d H:i') : '-',
                'created_at' => \Carbon\Carbon::parse($item->created_at)->format('Y/m/d H:i'),
                'url' => $item->getUrl(),
            ];
        }

        // HTMLを直接生成して返す
        return $this->generateGridHtml($displayItems);
    }

    /**
     * グリッドHTMLを生成
     */
    protected function generateGridHtml($items)
    {
        $html = '
        <div class="box box-solid">
            <div class="box-header with-border">
                <h3 class="box-title">お知らせ閲覧状況</h3>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th style="width: 80px;">ID</th>
                            <th>タイトル</th>
                            <th style="width: 200px;">閲覧状態</th>
                            <th style="width: 150px;">公開開始</th>
                            <th style="width: 150px;">作成日時</th>
                            <th style="width: 100px;">操作</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        if (count($items) > 0) {
            foreach ($items as $item) {
                $html .= '<tr>';
                $html .= '<td>' . htmlspecialchars($item['id']) . '</td>';
                $html .= '<td><a href="' . htmlspecialchars($item['url']) . '" style="color: #337ab7;">' . htmlspecialchars($item['title']) . '</a></td>';
                
                // 閲覧状態
                if ($item['is_viewed']) {
                    $html .= '<td>';
                    $html .= '<span style="color: #28a745; font-weight: bold;"><i class="fa fa-check-circle"></i> 既読</span><br>';
                    $html .= '<small style="color: #666;">最終閲覧: ' . htmlspecialchars($item['last_viewed_at']) . ' (' . htmlspecialchars($item['view_count']) . '回)</small>';
                    $html .= '</td>';
                } else {
                    $html .= '<td><span style="color: #dc3545; font-weight: bold;"><i class="fa fa-times-circle"></i> 未読</span></td>';
                }
                
                $html .= '<td>' . htmlspecialchars($item['start_datetime']) . '</td>';
                $html .= '<td>' . htmlspecialchars($item['created_at']) . '</td>';
                $html .= '<td><a href="' . htmlspecialchars($item['url']) . '" class="btn btn-sm btn-primary"><i class="fa fa-eye"></i> 表示</a></td>';
                $html .= '</tr>';
            }
        } else {
            $html .= '<tr><td colspan="6" style="text-align: center; padding: 40px; color: #999;">';
            $html .= '<i class="fa fa-inbox" style="font-size: 48px; margin-bottom: 10px; display: block;"></i>';
            $html .= 'お知らせがありません</td></tr>';
        }
        
        $html .= '
                    </tbody>
                </table>
            </div>
        </div>
        <style>
        .table > thead > tr > th {
            background-color: #f8f9fa;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }
        .table > tbody > tr:hover {
            background-color: #f1f3f5;
        }
        .table > tbody > tr > td {
            vertical-align: middle;
        }
        </style>';
        
        return $html;
    }

    /**
     * ビューオプションフォームの設定
     */
    public function setViewOptionForm($form)
    {
        $form->description('このビューでは、お知らせの閲覧状況（既読/未読）を表示します。');
        
        // フィルタ設定を追加
        static::setFilterFields($form, $this->custom_table);
        
        // 並べ替え設定を追加
        static::setSortFields($form, $this->custom_table);
    }
}
