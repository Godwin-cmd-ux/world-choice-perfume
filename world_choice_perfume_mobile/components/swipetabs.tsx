/**
 * Swipe between the bottom tabs.
 *
 * react-navigation's bottom tabs do not answer a swipe — only a tap on the bar
 * does — so a phone user who swipes across a tab gets nothing. This adds the
 * gesture the bar was missing: swipe left for the next tab, right for the
 * previous one. The first tab does not swipe back past itself and the last does
 * not swipe on past itself.
 *
 * It is built on react-native-gesture-handler, which the app already ships
 * natively, so this is a JavaScript-only change and can be delivered as an OTA
 * update — a new native module would have forced a fresh build instead.
 *
 * Two things it deliberately does not do:
 *
 *   - it does not steal a vertical drag, so the long scrolling pages still
 *     scroll: the pan only takes over once the finger has travelled further
 *     sideways than it has up or down (SWIPE.activate / SWIPE.fail);
 *   - it does not steal a horizontal list either. A chip row or a brand
 *     carousel is rendered as <SwipeHoldScrollView>; a drag that starts there
 *     fails the tab pan outright, so the row scrolls exactly as before and the
 *     tab stays put.
 *
 * Wire it through the navigator's `screenLayout` so every tab gets it from one
 * place (app/(tabs)/_layout.tsx).
 */
import { createContext, useContext, useMemo, useRef, type ReactNode } from 'react';
import { ScrollView, View, type ScrollViewProps } from 'react-native';
import { Gesture, GestureDetector } from 'react-native-gesture-handler';
import { SWIPE, swipeDirection, swipeTarget } from '../lib/swipe';

/** The slice of the navigator's navigation object this needs. */
type TabNavigation = {
  getState: () => { index?: number; routeNames?: readonly string[] } | undefined;
  navigate: (name: never) => void;
};

/** Set while a touch belongs to a horizontal list; see <SwipeHoldScrollView>. */
const HoldContext = createContext<{ current: boolean } | null>(null);

/**
 * Wraps the content of one tab screen. Pass the navigator's own `navigation`
 * (from the tab layout's `screenLayout`), and the screen inside it.
 */
export function SwipeTabScreen({
  navigation,
  children,
}: {
  navigation: TabNavigation;
  children: ReactNode;
}) {
  // Read by the gesture below, written by <SwipeHoldScrollView>.
  const held = useRef(false);
  const origin = useRef({ x: 0, y: 0 });

  const pan = useMemo(
    () =>
      Gesture.Pan()
        // Plain JavaScript handlers: the decision is a couple of comparisons at
        // the end of the drag, so there is nothing to gain by moving it off the
        // JS thread, and this keeps the code readable.
        .runOnJS(true)
        // The gesture decides for itself when it starts, so a drag that belongs
        // to a list underneath never reaches the tab bar.
        .manualActivation(true)
        .onTouchesDown((event, manager) => {
          const touch = event.allTouches[0];
          if (touch) origin.current = { x: touch.absoluteX, y: touch.absoluteY };

          // A horizontal list under the finger keeps its drag untouched: fail
          // before the gesture is ever considered, so nothing is cancelled.
          if (held.current) manager.fail();
        })
        .onTouchesMove((event, manager) => {
          if (held.current) {
            manager.fail();
            return;
          }

          const touch = event.allTouches[0];
          if (!touch) return;

          const dx = touch.absoluteX - origin.current.x;
          const dy = touch.absoluteY - origin.current.y;

          // Up or down and no further sideways: this is a scroll, and the page
          // keeps the drag (failing releases it untouched).
          if (Math.abs(dy) > SWIPE.fail && Math.abs(dy) > Math.abs(dx)) {
            manager.fail();
            return;
          }

          // Sideways: the tab bar answers it.
          if (Math.abs(dx) >= SWIPE.activate && Math.abs(dx) > Math.abs(dy)) manager.activate();
        })
        .onTouchesUp(() => {
          // The touch is over, whoever it belonged to.
          held.current = false;
        })
        .onEnd((event) => {
          if (held.current) return;

          const direction = swipeDirection({
            dx: event.translationX,
            dy: event.translationY,
            vx: event.velocityX,
          });

          if (!direction) return;

          const target = swipeTarget(navigation.getState(), direction);
          if (!target) return;

          navigation.navigate(target as never);
        }),
    [navigation],
  );

  return (
    <HoldContext.Provider value={held}>
      <GestureDetector gesture={pan}>
        {/* The swipe must not add a layout of its own: the screen keeps its
            ScrollView, its safe areas and its pull-to-refresh. */}
        <View style={{ flex: 1 }} collapsable={false}>
          {children}
        </View>
      </GestureDetector>
    </HoldContext.Provider>
  );
}

/**
 * A horizontal ScrollView that owns the drag starting inside it — the shop's
 * chip row, the home page's brand carousel. Rendering the list through this
 * instead of ScrollView tells the tab swipe to stand down while the finger is
 * on the list, so sideways drags keep scrolling the list rather than moving the
 * tab. Vertical ScrollViews need nothing: they never touch the tab swipe.
 */
export function SwipeHoldScrollView(props: ScrollViewProps) {
  const held = useContext(HoldContext);

  const hold = (value: boolean) => {
    if (held) held.current = value;
  };

  return (
    <ScrollView
      {...props}
      // The list's own scrolling is untouched: these only record that the touch
      // began on a horizontal list.
      onTouchStart={(event) => {
        hold(true);
        props.onTouchStart?.(event);
      }}
      onTouchEnd={(event) => {
        hold(false);
        props.onTouchEnd?.(event);
      }}
      onTouchCancel={(event) => {
        hold(false);
        props.onTouchCancel?.(event);
      }}
    />
  );
}
