import './bootstrap';

import Alpine from 'alpinejs';
import intersect from '@alpinejs/intersect';
import axios from 'axios';

Alpine.plugin(intersect);
window.Alpine = Alpine;
window.axios = axios;

// Configure axios default headers
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

Alpine.start();
