<?php
/**
 * Plugin Name: CWA Earthquake Data Display
 * Description: 顯示中央氣象署最新地震報告資料
 * Version: 1.0.0
 * Author: CJC102
 */

// 防止直接訪問
if (!defined('ABSPATH')) {
    exit;
}

function display_cwa_earthquake_list() {
    // 檢查 CWA_API_TOKEN 是否已設定
    if (!defined('CWA_API_TOKEN') || empty(CWA_API_TOKEN)) {
        return '<p>⚠️ CWA API Token 未設定，請聯絡系統管理員。</p>';
    }
    
    // 1. 構建 API 請求 URL（使用環境變數中的 token）
    $api_url = add_query_arg(
        array(
            'Authorization' => CWA_API_TOKEN,
            'limit' => '10',
            'format' => 'JSON'
        ),
        'https://opendata.cwa.gov.tw/api/v1/rest/datastore/E-A0015-001'
    );
    
    // 2. 使用 WordPress HTTP API 取得資料
    $response = wp_remote_get($api_url, array(
        'timeout' => 10,
        'sslverify' => true
    ));
    
    // 3. 檢查請求是否成功
    if (is_wp_error($response)) {
        error_log('CWA API Error: ' . $response->get_error_message());
        return '<p>⚠️ 無法取得地震資料，請稍後再試。</p>';
    }
    
    // 4. 解析 JSON 回應
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    // 5. 檢查資料結構是否符合氣象署規範
    if (empty($data) || !isset($data['records']['Earthquake'])) {
        error_log('CWA API: Invalid data structure received');
        return '<p>目前沒有最新的地震資料，或資料格式讀取中。</p>';
    }

    $earthquakes = $data['records']['Earthquake']; // 取得地震資料
    
    // 6. 開始建立 HTML 結構
    $output = '<div class="earthquake-wrap" style="background:#f8f9fa; padding:20px; border-radius:8px; max-width: 600px;">';
    $output .= '<h2 style="border-bottom:2px solid #0073aa; padding-bottom:10px; margin-top:0;">最新地震報告</h2>';

    foreach ($earthquakes as $eq) {
        $info = $eq['EarthquakeInfo'];
        $report_url = $eq['ReportImageURI']; // 地震報告圖檔網址
        
        $output .= '<div style="background:#fff; border:1px solid #ddd; margin-bottom:15px; padding:15px; border-radius:5px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">';
        
        // 標題：地點
        $output .= '<div style="font-size:1.1em; font-weight:bold; color:#d93025; margin-bottom:8px;">📍 ' . esc_html($info['Epicenter']['Location']) . '</div>';
        
        // 詳細資訊
        $output .= '<div style="font-size:0.9em; color:#444; line-height:1.6;">';
        $output .= '<div><strong>時間：</strong>' . esc_html($info['OriginTime']) . '</div>';
        $output .= '<div><strong>規模：</strong><span style="font-size:1.1em; color:#d93025; font-weight:bold;">' . esc_html($info['EarthquakeMagnitude']['MagnitudeValue']) . '</span></div>';
        
        // 只有當 Depth 資料存在時才顯示深度資訊
        if (isset($info['Depth']) && isset($info['Depth']['Value'])) {
            $output .= '<div><strong>深度：</strong>' . esc_html($info['Depth']['Value']) . ' ' . esc_html($info['Depth']['Unit']) . '</div>';
        }
        
        $output .= '</div>';
        
        // 按鈕
        $output .= '<a href="' . esc_url($report_url) . '" target="_blank" style="display:inline-block; margin-top:12px; background:#0073aa; color:#fff; padding:5px 12px; text-decoration:none; border-radius:4px; font-size:0.85em;">🔍 查看官方圖表</a>';
        
        $output .= '</div>';
    }

    $output .= '</div>';
    return $output;
}

// 註冊短碼 [my_eq_list]
add_shortcode('my_eq_list', 'display_cwa_earthquake_list');
