# World Choice Perfume — Mobile

The mobile app for **World Choice Perfume**, an Expo (React Native) project using
[expo-router](https://docs.expo.dev/router/introduction/) for file-based routing.

This is a fresh, blank Expo project: it contains the app shell and a starter
welcome screen, ready for the World Choice Perfume mobile app to be built on top.

## Get started

1. Install dependencies

   ```bash
   npm install
   ```

2. Start the app

   ```bash
   npx expo start
   ```

   Then open it in an Android emulator, an iOS simulator, or
   [Expo Go](https://expo.dev/go).

## Project structure

```
app/
  _layout.tsx   # Root layout (stack, no header)
  index.tsx     # Welcome homepage — start building here
assets/images/  # App icon, splash and favicon
app.json        # Expo config (name, slug, package ids, plugins)
```

Add a screen by creating a file under `app/` — expo-router turns each file into a
route automatically.

## Next steps

- Build the shop: product catalogue, cart and checkout.
- Wire up the backend API used by the website.
- Replace the placeholder icons in `assets/images/` with your own branding.
- Set the final `slug`, `android.package` and `ios.bundleIdentifier` in `app.json`
  before publishing, and create a new EAS project for this app.
