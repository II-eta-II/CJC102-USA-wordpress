function display_cwa_earthquake_list() {
    // 1. 調用 WPGetAPI 的資料
    $data = wpgetapi_endpoint( 'get_earthquake_report', 'get_earthquake_report', array('debug' => false) );

    // 2. [關鍵修正] 如果回傳的是 JSON 字串，先將其轉換為 PHP 陣列
    if ( is_string( $data ) ) {
        $data = json_decode( $data, true );
    }

    // 3. 檢查資料結構是否符合氣象署規範
    // 注意：氣象署的 Key 大小寫必須完全一致 (Earthquake)
    if ( empty( $data ) || !isset( $data['records']['Earthquake'] ) ) {
        // 如果還是失敗，可以輸出這行來除錯 (發布後可移除)
        // return '<pre>資料格式不符：' . print_r($data, true) . '</pre>'; 
        return '<p>目前沒有最新的地震資料，或資料格式讀取中。</p>';
    }

    $earthquakes = $data['records']['Earthquake']; // 取得 10 筆地震資料
    
    // 4. 開始建立 HTML 結構
    $output = '<div class="earthquake-wrap" style="background:#f8f9fa; padding:20px; border-radius:8px; max-width: 600px;">';
    $output .= '<h2 style="border-bottom:2px solid #0073aa; padding-bottom:10px; margin-top:0;">最新地震報告</h2>';

    foreach ( $earthquakes as $eq ) {
        $info = $eq['EarthquakeInfo'];
        $report_url = $eq['ReportImageURI']; // 地震報告圖檔網址
        
        $output .= '<div style="background:#fff; border:1px solid #ddd; margin-bottom:15px; padding:15px; border-radius:5px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">';
        
        // 標題：地點
        $output .= '<div style="font-size:1.1em; font-weight:bold; color:#d93025; margin-bottom:8px;">📍 ' . esc_html($info['Epicenter']['Location']) . '</div>';
        
        // 詳細資訊
        $output .= '<div style="font-size:0.9em; color:#444; line-height:1.6;">';
        $output .= '<div><strong>時間：</strong>' . esc_html($info['OriginTime']) . '</div>';
        $output .= '<div><strong>規模：</strong><span style="font-size:1.1em; color:#d93025; font-weight:bold;">' . esc_html($info['EarthquakeMagnitude']['MagnitudeValue']) . '</span></div>';
        $output .= '<div><strong>深度：</strong>' . esc_html($info['Depth']['Value']) . ' ' . esc_html($info['Depth']['Unit']) . '</div>';
        $output .= '</div>';
        
        // 按鈕
        $output .= '<a href="' . esc_url($report_url) . '" target="_blank" style="display:inline-block; margin-top:12px; background:#0073aa; color:#fff; padding:5px 12px; text-decoration:none; border-radius:4px; font-size:0.85em;">🔍 查看官方圖表</a>';
        
        $output .= '</div>';
    }

    $output .= '</div>';
    return $output;
}

// 註冊短碼 [my_eq_list]
add_shortcode( 'my_eq_list', 'display_cwa_earthquake_list' );
