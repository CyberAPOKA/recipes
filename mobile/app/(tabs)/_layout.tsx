import { Tabs } from "expo-router";
import React from "react";
import { Platform } from "react-native";

import { Colors } from "@/constants/theme";
import { useColorScheme } from "@/hooks/use-color-scheme";

export default function TabLayout() {
  const colorScheme = useColorScheme();

  return (
    <Tabs
      screenOptions={{
        tabBarActiveTintColor: Colors[colorScheme ?? "light"].tint,
        headerShown: false,
        // Esconde completamente a tab bar padrão - usamos BottomNavigation customizado
        tabBarStyle: Platform.select({
          web: {
            display: "none",
            height: 0,
            position: "absolute",
            bottom: -100,
          },
          default: { display: "none", height: 0 },
        }),
        tabBarButton: () => null,
        tabBarShowLabel: false,
        tabBarIcon: () => null,
      }}
    >
      <Tabs.Screen
        name="index"
        options={{
          title: "Home",
          href: null, // Não aparece na tab bar
        }}
      />
      <Tabs.Screen
        name="explore"
        options={{
          href: null, // Não aparece na tab bar
        }}
      />
      <Tabs.Screen
        name="recipes"
        options={{
          href: null, // Não aparece na tab bar
        }}
      />
    </Tabs>
  );
}
