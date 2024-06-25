import Site from './SiteApplication';

const app = new Site();

// @ts-expect-error 出于向后兼容的目的，我们需要这样做。
window.app = app;

export default app;
