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
            <tbody>
                @if(count($items) > 0)
                    @foreach($items as $item)
                        <tr>
                            <td>{{ $item['id'] }}</td>
                            <td>
                                <a href="{{ $item['url'] }}" style="color: #337ab7;">
                                    {{ $item['title'] }}
                                </a>
                            </td>
                            <td>
                                @if($item['is_viewed'])
                                    <span style="color: #28a745; font-weight: bold;">
                                        <i class="fa fa-check-circle"></i> 既読
                                    </span>
                                    <br>
                                    <small style="color: #666;">
                                        最終閲覧: {{ $item['last_viewed_at'] }} ({{ $item['view_count'] }}回)
                                    </small>
                                @else
                                    <span style="color: #dc3545; font-weight: bold;">
                                        <i class="fa fa-times-circle"></i> 未読
                                    </span>
                                @endif
                            </td>
                            <td>{{ $item['start_datetime'] }}</td>
                            <td>{{ $item['created_at'] }}</td>
                            <td>
                                <a href="{{ $item['url'] }}" class="btn btn-sm btn-primary">
                                    <i class="fa fa-eye"></i> 表示
                                </a>
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px; color: #999;">
                            <i class="fa fa-inbox" style="font-size: 48px; margin-bottom: 10px; display: block;"></i>
                            お知らせがありません
                        </td>
                    </tr>
                @endif
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
</style>
