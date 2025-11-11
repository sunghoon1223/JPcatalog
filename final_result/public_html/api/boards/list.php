<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');

$boards = [
    ['id' => 'notice', 'slug' => 'notice', 'title' => '공지사항', 'icon' => '📢', 'postCount' => 5],
    ['id' => 'qna', 'slug' => 'qna', 'title' => 'Q&A', 'icon' => '❓', 'postCount' => 12],
    ['id' => 'review', 'slug' => 'review', 'title' => '제품 리뷰', 'icon' => '⭐', 'postCount' => 28],
    ['id' => 'free', 'slug' => 'free', 'title' => '자유게시판', 'icon' => '💬', 'postCount' => 45]
];

echo json_encode(['success' => true, 'boards' => $boards], JSON_UNESCAPED_UNICODE);
?>