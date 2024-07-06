import Admin from './AdminApplication';

const app = new Admin();

// @ts-expect-error 出于向后兼容性的目的，我们需要这样做。
window.app = app;

export default app;
