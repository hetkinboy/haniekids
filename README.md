# TikTok Shop Cost Management Planning

Bộ tài liệu phân tích và lập kế hoạch triển khai phần mềm tính chi phí vận hành TikTok Shop bằng CodeIgniter 4 + Angular.

## Cấu trúc

```text
docs/
  05-ui-flow.md
  08-codex-guide.md

tasks/
  06-backend-ci4-tasks.md
  07-frontend-angular-tasks.md
  task-list.md
  current-task.md
```

## Cách dùng

1. Đưa toàn bộ folder này cho Codex.
2. Yêu cầu Codex đọc `docs/08-codex-guide.md` trước.
3. Sau đó yêu cầu Codex làm theo `tasks/current-task.md`.
4. Khi xong task hiện tại, chuyển sang task kế tiếp trong `tasks/task-list.md`.

## Nghiệp vụ quan trọng

Tồn kho thật nằm theo size, không nằm theo combo.

Ví dụ:

```text
Size 5 tồn 10 bộ.
Bán Size 5 - Combo 3, số lượng 2.
Phải trừ 6 bộ.
Tồn còn 4 bộ.
```
