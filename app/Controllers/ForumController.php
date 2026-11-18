<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;

final class ForumController
{
    public function index(): void
    {
        $categories = Database::fetchAll('SELECT * FROM forum_categories ORDER BY position, id');
        $boards = Database::fetchAll(
            'SELECT b.*,
                (SELECT COUNT(*) FROM forum_topics t WHERE t.board_id = b.id) AS topic_count,
                (SELECT COUNT(*) FROM forum_posts p JOIN forum_topics t2 ON t2.id = p.topic_id WHERE t2.board_id = b.id) AS post_count,
                (SELECT t3.title FROM forum_topics t3 WHERE t3.board_id = b.id ORDER BY t3.updated_at DESC LIMIT 1) AS last_topic,
                (SELECT u.username FROM forum_topics t4 JOIN users u ON u.id = t4.author_id WHERE t4.board_id = b.id ORDER BY t4.updated_at DESC LIMIT 1) AS last_author,
                (SELECT t5.updated_at FROM forum_topics t5 WHERE t5.board_id = b.id ORDER BY t5.updated_at DESC LIMIT 1) AS last_at
             FROM forum_boards b ORDER BY b.position, b.id'
        );
        $grouped = [];
        foreach ($categories as $category) {
            $category['boards'] = array_values(array_filter(
                $boards,
                static fn (array $board): bool => (int) $board['category_id'] === (int) $category['id']
            ));
            $grouped[] = $category;
        }
        $totals = Database::fetch(
            'SELECT (SELECT COUNT(*) FROM forum_topics) AS topics,
                    (SELECT COUNT(*) FROM forum_posts) AS posts,
                    (SELECT COUNT(*) FROM users) AS members'
        );
        View::render('community/index', [
            'title' => t('Community'),
            'categories' => $grouped,
            'totals' => [
                'topics' => (int) ($totals['topics'] ?? 0),
                'posts' => (int) ($totals['posts'] ?? 0),
                'members' => (int) ($totals['members'] ?? 0),
            ],
        ]);
    }

    public function board(string $board): void
    {
        $board = $this->boardOr404($board);
        $topics = Database::fetchAll(
            'SELECT t.*, u.username,
                (SELECT COUNT(*) FROM forum_posts p WHERE p.topic_id = t.id) AS reply_count
             FROM forum_topics t JOIN users u ON u.id = t.author_id
             WHERE t.board_id = ? ORDER BY t.updated_at DESC LIMIT 100',
            [$board['id']]
        );
        $category = Database::fetch('SELECT * FROM forum_categories WHERE id = ?', [$board['category_id']]);
        View::render('community/board', [
            'title' => $board['name'] . ' · ' . t('Community'),
            'board' => $board,
            'category' => $category,
            'topics' => $topics,
        ]);
    }

    public function createTopic(string $board): void
    {
        Auth::requireUser();
        $board = $this->boardOr404($board);
        $error = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = trim((string) ($_POST['title'] ?? ''));
            $body = trim((string) ($_POST['body'] ?? ''));
            if ($title === '' || mb_strlen($title) > 200) {
                $error = t('Please provide a title of 1–200 characters.');
            } elseif ($body === '') {
                $error = t('Please write something in the message body.');
            } else {
                Database::execute(
                    'INSERT INTO forum_topics (board_id, author_id, title, body) VALUES (?, ?, ?, ?)',
                    [$board['id'], Auth::id(), $title, $body]
                );
                $topicId = (int) Database::pdo()->lastInsertId();
                flash('success', t('Topic published.'));
                redirect('/community/topic/' . $topicId);
            }
        }
        View::render('community/new_topic', [
            'title' => t('New topic') . ' · ' . $board['name'],
            'board' => $board,
            'error' => $error,
            'values' => ['title' => $_POST['title'] ?? '', 'body' => $_POST['body'] ?? ''],
        ]);
    }

    public function topic(string $topic): void
    {
        $topic = Database::fetch(
            'SELECT t.*, u.username FROM forum_topics t JOIN users u ON u.id = t.author_id WHERE t.id = ?',
            [(int) $topic]
        );
        if (!$topic) {
            abort(404);
        }
        Database::execute('UPDATE forum_topics SET views = views + 1 WHERE id = ?', [$topic['id']]);
        $posts = Database::fetchAll(
            'SELECT p.*, u.username FROM forum_posts p JOIN users u ON u.id = p.author_id
             WHERE p.topic_id = ? ORDER BY p.created_at ASC, p.id ASC LIMIT 500',
            [$topic['id']]
        );
        $board = Database::fetch('SELECT * FROM forum_boards WHERE id = ?', [$topic['board_id']]);
        View::render('community/topic', [
            'title' => $topic['title'] . ' · ' . t('Community'),
            'topic' => $topic,
            'posts' => $posts,
            'board' => $board,
        ]);
    }

    public function reply(string $topic): void
    {
        Auth::requireUser();
        $topic = Database::fetch('SELECT * FROM forum_topics WHERE id = ?', [(int) $topic]);
        if (!$topic) {
            abort(404);
        }
        if ((int) $topic['is_locked'] === 1) {
            flash('error', t('This topic is locked.'));
            redirect('/community/topic/' . $topic['id']);
        }
        $body = trim((string) ($_POST['body'] ?? ''));
        if ($body === '') {
            flash('error', t('Please write something before posting.'));
            redirect('/community/topic/' . $topic['id']);
        }
        Database::execute(
            'INSERT INTO forum_posts (topic_id, author_id, body) VALUES (?, ?, ?)',
            [$topic['id'], Auth::id(), $body]
        );
        $postId = (int) Database::pdo()->lastInsertId();
        Database::execute('UPDATE forum_topics SET updated_at = CURRENT_TIMESTAMP WHERE id = ?', [$topic['id']]);
        flash('success', t('Reply posted.'));
        redirect('/community/topic/' . $topic['id'] . '#reply-' . $postId);
    }

    private function boardOr404(string $id): array
    {
        $board = Database::fetch('SELECT * FROM forum_boards WHERE id = ?', [(int) $id]);
        if (!$board) {
            abort(404);
        }
        return $board;
    }
}
