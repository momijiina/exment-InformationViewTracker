<?php

namespace App\Plugins\InformationViewTracker;

use Exceedone\Exment\Services\Plugin\PluginPageBase;
use Exceedone\Exment\Model\CustomTable;
use Carbon\Carbon;

class PluginPage extends PluginPageBase
{
    /**
     * トップページ（未閲覧ユーザー一覧にリダイレクト）
     */
    public function index()
    {
        return $this->unread();
    }

    /**
     * 未閲覧ユーザー一覧表示
     */
    public function unread()
    {
        // お知らせテーブルを取得
        $informationTable = CustomTable::getEloquent('information');
        if (!$informationTable) {
            return $this->errorHtml('お知らせテーブルが見つかりません');
        }

        // ユーザーテーブルを取得
        $userTable = CustomTable::getEloquent('user');
        if (!$userTable) {
            return $this->errorHtml('ユーザーテーブルが見つかりません');
        }

        // 閲覧記録テーブルを取得
        $viewsTable = CustomTable::getEloquent('information_views');
        if (!$viewsTable) {
            return $this->errorHtml('information_viewsテーブルが見つかりません');
        }

        // お知らせIDを取得（クエリパラメータから）
        $informationId = request()->get('information_id');
        
        // 統計データを取得
        $statistics = $this->getUnreadStatistics($informationTable, $userTable, $viewsTable, $informationId);
        
        // HTMLを生成
        return $this->generateUnreadHtml($statistics, $informationId);
    }

    /**
     * 未閲覧ユーザーCSVエクスポート
     */
    public function export()
    {
        // お知らせテーブルを取得
        $informationTable = CustomTable::getEloquent('information');
        if (!$informationTable) {
            return response('お知らせテーブルが見つかりません', 500);
        }

        // ユーザーテーブルを取得
        $userTable = CustomTable::getEloquent('user');
        if (!$userTable) {
            return response('ユーザーテーブルが見つかりません', 500);
        }

        // 閲覧記録テーブルを取得
        $viewsTable = CustomTable::getEloquent('information_views');
        if (!$viewsTable) {
            return response('information_viewsテーブルが見つかりません', 500);
        }

        // お知らせIDを取得
        $informationId = request()->get('information_id');
        
        // 統計データを取得
        $statistics = $this->getUnreadStatistics($informationTable, $userTable, $viewsTable, $informationId);
        
        // CSV生成
        return $this->generateCsv($statistics, $informationId);
    }

    /**
     * 未閲覧統計を取得
     */
    protected function getUnreadStatistics($informationTable, $userTable, $viewsTable, $informationId = null)
    {
        $result = [];

        // お知らせ一覧を取得
        $informationQuery = $informationTable->getValueModel();
        if ($informationId) {
            $informationQuery->where('id', $informationId);
        }
        $informations = $informationQuery->orderBy('created_at', 'desc')->get();

        // 全ユーザーを取得
        $allUsers = $userTable->getValueModel()->get();
        $totalUsers = $allUsers->count();

        foreach ($informations as $info) {
            $infoId = $info->id;
            $infoTitle = $info->getValue('title', '(タイトルなし)');

            // このお知らせの閲覧記録を取得
            $viewedUserIds = $viewsTable->getValueModel()
                ->where('value->information_id', $infoId)
                ->get()
                ->pluck('value')
                ->map(function($value) {
                    return is_array($value) ? ($value['user_id'] ?? null) : null;
                })
                ->filter()
                ->toArray();

            // 未閲覧ユーザーを抽出
            $unreadUsers = [];
            foreach ($allUsers as $user) {
                $userId = $user->id;
                if (!in_array($userId, $viewedUserIds)) {
                    $unreadUsers[] = [
                        'user_id' => $userId,
                        'user_name' => $user->getValue('user_name', ''),
                        'email' => $user->getValue('email', ''),
                    ];
                }
            }

            $result[] = [
                'id' => $infoId,
                'title' => $infoTitle,
                'created_at' => Carbon::parse($info->created_at)->format('Y/m/d H:i'),
                'total_users' => $totalUsers,
                'viewed_count' => count($viewedUserIds),
                'unread_count' => count($unreadUsers),
                'unread_users' => $unreadUsers,
                'url' => $info->getUrl(),
            ];
        }

        return $result;
    }

    /**
     * 未閲覧ユーザー一覧HTML生成
     */
    protected function generateUnreadHtml($statistics, $informationId = null)
    {
        $exportUrl = $this->plugin->getFullUrl('export');
        if ($informationId) {
            $exportUrl .= '?information_id=' . $informationId;
        }

        $html = '
        <div class="box box-solid">
            <div class="box-header with-border" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <h3 class="box-title" style="font-size: 18px;">📊 お知らせ未閲覧ユーザー一覧</h3>
                <div class="box-tools pull-right">
                    <a href="' . htmlspecialchars($exportUrl) . '" class="btn btn-success btn-sm" target="_blank" download>
                        <i class="fa fa-download"></i> CSVエクスポート
                    </a>
                </div>
            </div>
            <div class="box-body">';

        if (empty($statistics)) {
            $html .= '<div style="text-align: center; padding: 40px; color: #999;">
                <i class="fa fa-inbox" style="font-size: 48px; margin-bottom: 10px; display: block;"></i>
                お知らせがありません
            </div>';
        } else {
            foreach ($statistics as $stat) {
                $readPercentage = $stat['total_users'] > 0 
                    ? round(($stat['viewed_count'] / $stat['total_users']) * 100, 1) 
                    : 0;
                $progressColor = $readPercentage >= 80 ? '#28a745' : ($readPercentage >= 50 ? '#ffc107' : '#dc3545');

                $html .= '
                <div style="margin-bottom: 25px; border: 1px solid #e9ecef; border-radius: 5px; padding: 15px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <h4 style="margin: 0; color: #333;">
                            <a href="' . htmlspecialchars($stat['url']) . '" target="_blank" style="color: #337ab7;">
                                ' . htmlspecialchars($stat['title']) . '
                            </a>
                        </h4>
                        <small style="color: #666;">作成日時: ' . htmlspecialchars($stat['created_at']) . '</small>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 15px;">
                        <div style="background: #f8f9fa; padding: 10px; border-radius: 3px; text-align: center;">
                            <div style="font-size: 20px; font-weight: bold; color: #6c757d;">' . $stat['total_users'] . '</div>
                            <div style="font-size: 12px; color: #666;">全ユーザー</div>
                        </div>
                        <div style="background: #d4edda; padding: 10px; border-radius: 3px; text-align: center;">
                            <div style="font-size: 20px; font-weight: bold; color: #28a745;">' . $stat['viewed_count'] . '</div>
                            <div style="font-size: 12px; color: #155724;">既読</div>
                        </div>
                        <div style="background: #f8d7da; padding: 10px; border-radius: 3px; text-align: center;">
                            <div style="font-size: 20px; font-weight: bold; color: #dc3545;">' . $stat['unread_count'] . '</div>
                            <div style="font-size: 12px; color: #721c24;">未読</div>
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                            <span style="font-size: 13px; color: #333; font-weight: bold;">閲覧率</span>
                            <span style="font-size: 13px; color: ' . $progressColor . '; font-weight: bold;">' . $readPercentage . '%</span>
                        </div>
                        <div style="background: #e9ecef; height: 20px; border-radius: 10px; overflow: hidden;">
                            <div style="background: ' . $progressColor . '; height: 100%; width: ' . $readPercentage . '%;"></div>
                        </div>
                    </div>';

                if (!empty($stat['unread_users'])) {
                    $html .= '
                    <details style="margin-top: 10px;">
                        <summary style="cursor: pointer; font-weight: bold; color: #dc3545; padding: 5px;">
                            <i class="fa fa-users"></i> 未閲覧ユーザー一覧 (' . count($stat['unread_users']) . '名)
                        </summary>
                        <div style="margin-top: 10px; max-height: 300px; overflow-y: auto; background: #f8f9fa; padding: 10px; border-radius: 3px;">
                            <table style="width: 100%; font-size: 13px;">
                                <thead>
                                    <tr style="border-bottom: 2px solid #dee2e6;">
                                        <th style="padding: 5px; text-align: left;">ユーザー名</th>
                                        <th style="padding: 5px; text-align: left;">メールアドレス</th>
                                    </tr>
                                </thead>
                                <tbody>';
                    
                    foreach ($stat['unread_users'] as $user) {
                        $html .= '
                                    <tr style="border-bottom: 1px solid #e9ecef;">
                                        <td style="padding: 5px;">' . htmlspecialchars($user['user_name']) . '</td>
                                        <td style="padding: 5px;">' . htmlspecialchars($user['email']) . '</td>
                                    </tr>';
                    }
                    
                    $html .= '
                                </tbody>
                            </table>
                        </div>
                    </details>';
                }

                $html .= '</div>';
            }
        }

        $html .= '
            </div>
        </div>';

        return $html;
    }

    /**
     * CSV生成
     */
    protected function generateCsv($statistics, $informationId = null)
    {
        $filename = 'unread_users_' . date('YmdHis') . '.csv';
        
        // CSVデータを生成
        $csvData = '';
        
        // BOM追加（Excel対応）
        $csvData .= chr(0xEF).chr(0xBB).chr(0xBF);
        
        // ヘッダー行
        $headers = ['お知らせID', 'お知らせタイトル', '作成日時', '全ユーザー数', '既読数', '未読数', 'ユーザーID', 'ユーザー名', 'メールアドレス'];
        $csvData .= '"' . implode('","', $headers) . '"' . "\n";
        
        // データ行
        foreach ($statistics as $stat) {
            if (empty($stat['unread_users'])) {
                // 未閲覧ユーザーがいない場合も1行出力
                $row = [
                    $stat['id'],
                    $stat['title'],
                    $stat['created_at'],
                    $stat['total_users'],
                    $stat['viewed_count'],
                    $stat['unread_count'],
                    '',
                    '',
                    ''
                ];
                $csvData .= '"' . implode('","', array_map(function($v) {
                    return str_replace('"', '""', $v);
                }, $row)) . '"' . "\n";
            } else {
                // 各未閲覧ユーザーごとに行を出力
                foreach ($stat['unread_users'] as $user) {
                    $row = [
                        $stat['id'],
                        $stat['title'],
                        $stat['created_at'],
                        $stat['total_users'],
                        $stat['viewed_count'],
                        $stat['unread_count'],
                        $user['user_id'],
                        $user['user_name'],
                        $user['email']
                    ];
                    $csvData .= '"' . implode('","', array_map(function($v) {
                        return str_replace('"', '""', $v);
                    }, $row)) . '"' . "\n";
                }
            }
        }
        
        // レスポンスを返す
        return response($csvData, 200, [
            'Content-Type' => 'application/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0'
        ]);
    }

    /**
     * エラーHTML生成
     */
    protected function errorHtml($message)
    {
        return '
        <div class="box box-solid">
            <div class="box-body" style="text-align: center; padding: 40px;">
                <i class="fa fa-exclamation-triangle" style="font-size: 48px; color: #dc3545; margin-bottom: 15px; display: block;"></i>
                <h4 style="color: #dc3545; margin-bottom: 10px;">エラー</h4>
                <p style="color: #666;">' . htmlspecialchars($message) . '</p>
            </div>
        </div>';
    }
}
