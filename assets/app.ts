import { registerReactControllerComponents } from '@symfony/ux-react';
import './bootstrap';
/*
 * Welcome to your app's main TypeScript file!
 *
 * We recommend including the built version of this TypeScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.scss in this case)
import './styles/app.scss';

registerReactControllerComponents(require.context('./react/controllers', true, /\.tsx?$/));