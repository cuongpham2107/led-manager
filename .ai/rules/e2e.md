---
paths:
  - 'scripts/e2e/**'
---

# E2E

## browser-use + DeepSeek: thinking mode rejects tool_choice
DeepSeek's 'deepseek-flash' / 'deepseek-v4-pro' enable thinking mode BY DEFAULT server-side, and thinking mode refuses `tool_choice`. browser-use needs tool_choice for structured output, so the API returns HTTP 400 "Thinking mode does not support this tool_choice" and the agent dies with "Result failed 6/6 times".
browser_use's ChatDeepSeek only sends the disable flag when the model name contains 'deepseek-v4' (see _supports_thinking), so plain ChatDeepSeek(model='deepseek-flash') is 100% unusable. Fix used in browser_use_e2e.py: subclass ChatDeepSeek and force _supports_thinking() -> True, then construct with thinking=False.
Vision facts verified by hitting the API directly: deepseek-flash and deepseek-chat DO have vision; deepseek-v4-pro does NOT (it receives an "[Unsupported Image]" placeholder).

## E2E needs BOTH artisan serve and the Vite dev server running
public/hot is committed-adjacent local state (gitignored) and is often left behind pointing at http://[::1]:5173. If the Vite dev server is not actually running, Laravel's @vite sends every CSS/JS request to a dead port, so the panel renders unstyled with no Livewire and the agent fails in confusing ways.
Before running the E2E: confirm BOTH http://127.0.0.1:8000/login -> 200 AND http://[::1]:5173/@vite/client -> 200 (run `npm run dev`), or delete public/hot so the prebuilt public/build manifest is used instead.
Also: a previously failed run leaves an order in Dispatched plus assets InTransit, which keeps reserving stock and can block the next run at order creation ("Thiếu thiết bị khả dụng") — reset with `php artisan app:bootstrap`.
