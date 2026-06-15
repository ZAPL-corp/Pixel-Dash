<?php
// --- НАСТРОЙКА ПОДКЛЮЧЕНИЯ К БД GDPSFH ---
$db_host = "localhost"; 
$db_user = "gdps_pixeldash"; // Твой логин из панели
$db_pass = "5i80dsxmi8pva4tlu54tee"; // Нажми Copy password в панели и вставь сюда
$db_name = "gdps_pixeldash"; // На GDPSFH имя БД совпадает с логином

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Ошибка подключения к БД: " . $conn->connect_error);
}

// Получаем ID уровня из GET-запроса, если на него кликнули
$selected_level = isset($_GET['level_id']) ? intval($_GET['level_id']) : 0;
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pixel Dash — Demon List</title>
    <style>
        :root {
            --bg-color: #12141c;
            --card-bg: #1a1d29;
            --accent-color: #ff3366;
            --text-color: #e2e8f0;
            --text-muted: #8a99ad;
        }

        body {
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
        }

        .container {
            width: 100%;
            max-width: 1000px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        @media (max-width: 768px) {
            .container { grid-template-columns: 1fr; }
        }

        h1, h2 {
            color: #fff;
            margin-top: 0;
        }

        .panel {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.05);
        }

        /* Список уровней */
        .level-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-height: 80vh;
            overflow-y: auto;
            padding-right: 5px;
        }

        .level-item {
            background: rgba(255,255,255,0.02);
            padding: 15px;
            border-radius: 8px;
            text-decoration: none;
            color: inherit;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-left: 4px solid transparent;
            transition: all 0.2s ease;
        }

        .level-item:hover {
            background: rgba(255,255,255,0.05);
            border-left-color: var(--accent-color);
            transform: translateX(2px);
        }

        .level-item.active {
            background: rgba(255, 51, 102, 0.1);
            border-left-color: var(--accent-color);
        }

        .level-name {
            font-weight: bold;
            font-size: 1.1em;
            color: #fff;
        }

        .level-author {
            font-size: 0.85em;
            color: var(--text-muted);
            margin-top: 4px;
        }

        /* Информационная панель */
        .placeholder-text {
            color: var(--text-muted);
            text-align: center;
            margin-top: 40px;
        }

        .leaderboard-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .leaderboard-table th, .leaderboard-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }

        .leaderboard-table th {
            color: var(--text-muted);
            font-size: 0.9em;
        }

        .rank { font-weight: bold; color: var(--accent-color); width: 40px; }
    </style>
</head>
<body>

<div class="container">
    
    <!-- ЛЕВАЯ ЧАСТЬ: СПИСОК ДЕМОНОВ -->
    <div class="panel">
        <h2>Demon List</h2>
        <div class="level-list">
            <?php
            // Выбираем только демоны (starDemon = 1) и сортируем, например, по сложности или ID
            $levels_query = "SELECT l.levelID, l.levelName, u.userName 
                             FROM levels l 
                             LEFT JOIN users u ON l.userID = u.userID 
                             WHERE l.starDemon = 1 
                             ORDER BY l.levelID DESC";
            
            $levels_result = $conn->query($levels_query);

            if ($levels_result->num_rows > 0) {
                while($row = $levels_result->fetch_assoc()) {
                    $active_class = ($selected_level == $row['levelID']) ? 'active' : '';
                    echo "<a class='level-item {$active_class}' href='?level_id={$row['levelID']}'>";
                    echo "<div>";
                    echo "  <div class='level-name'>" . htmlspecialchars($row['levelName']) . "</div>";
                    echo "  <div class='level-author'>by " . htmlspecialchars($row['userName']) . "</div>";
                    echo "</div>";
                    echo "<div>ID: " . $row['levelID'] . "</div>";
                    echo "</a>";
                }
            } else {
                echo "<p class='placeholder-text'>Демоны на сервере пока не найдены.</p>";
            }
            ?>
        </div>
    </div>

    <!-- ПРАВАЯ ЧАСТЬ: СТАТИСТИКА УРОВНЯ -->
    <div class="panel">
        <?php
        if ($selected_level > 0) {
            // Запрос информации о выбранном уровне
            $level_info_stmt = $conn->prepare("SELECT l.levelName, u.userName, l.levelDesc 
                                               FROM levels l 
                                               LEFT JOIN users u ON l.userID = u.userID 
                                               WHERE l.levelID = ?");
            $level_info_stmt->bind_param("i", $selected_level);
            $level_info_stmt->execute();
            $level_info = $level_info_stmt->get_result()->fetch_assoc();

            if ($level_info) {
                echo "<h1>" . htmlspecialchars($level_info['levelName']) . "</h1>";
                echo "<p style='color: var(--text-muted);'>Создатель: <strong style='color:#fff;'>" . htmlspecialchars($level_info['userName']) . "</strong></p>";
                if(!empty($level_info['levelDesc'])) {
                    echo "<p style='font-style: italic; background: rgba(0,0,0,0.2); padding: 10px; border-radius: 5px;'>" . htmlspecialchars(base64_decode($level_info['levelDesc'])) . "</p>";
                }
                
                echo "<h3 style='margin-top: 30px;'>Последние 5 прохождений (Топ)</h3>";

                // Запрос топ-5 игроков, прошедших этот уровень на 100%
                // В стандартных GDPS прогресс хранится в таблице levelscores или аналогичной
                $lb_query = "SELECT u.userName, ls.percent 
                             FROM levelscores ls 
                             LEFT JOIN users u ON ls.accountID = u.extID 
                             WHERE ls.levelID = ? AND ls.percent = 100 
                             ORDER BY ls.scoreID ASC LIMIT 5"; 
                
                // Примечание: Если структура топ-левелов использует другую таблицу (например, рекорды), 
                // поле может называться иначе. Ниже приведена стандартная совместимость.
                
                $lb_stmt = $conn->prepare($lb_query);
                $lb_stmt->bind_param("i", $selected_level);
                $lb_stmt->execute();
                $lb_result = $lb_stmt->get_result();

                if ($lb_result->num_rows > 0) {
                    echo "<table class='leaderboard-table'>";
                    echo "<tr><th>#</th><th>Игрок</th><th>Прогресс</th></tr>";
                    $rank = 1;
                    while($lb_row = $lb_result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td class='rank'>#{$rank}</td>";
                        echo "<td>" . htmlspecialchars($lb_row['userName']) . "</td>";
                        echo "<td style='color: #2ecc71; font-weight: bold;'>" . $lb_row['percent'] . "%</td>";
                        echo "</tr>";
                        $rank++;
                    }
                    echo "</table>";
                } else {
                    echo "<p class='placeholder-text'>Этот уровень ещё никто не прошёл на 100%.</p>";
                }
            } else {
                echo "<h2>Уровень не найден</h2>";
            }
        } else {
            echo "<h2>Статистика уровня</h2>";
            echo "<p class='placeholder-text'>Выберите демон из списка слева, чтобы посмотреть создателя и топ прохождений.</p>";
        }
        ?>
    </div>

</div>

</body>
</html>
<?php $conn->close(); ?>
