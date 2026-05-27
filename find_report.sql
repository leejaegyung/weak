SELECT wr.id, wr.week, wr.curr_start::text, wr.status
FROM weekly_reports wr
JOIN users u ON u.id = wr.user_id
WHERE u.name = '이재경'
ORDER BY wr.created_at DESC
LIMIT 5;
