import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;
// Laravel이 내려주는 XSRF-TOKEN 쿠키 → 요청마다 X-XSRF-TOKEN 헤더(메타 csrf 고정값보다 안정적)
window.axios.defaults.xsrfCookieName = 'XSRF-TOKEN';
window.axios.defaults.xsrfHeaderName = 'X-XSRF-TOKEN';
