import { View, StyleSheet, TouchableOpacity, Text } from 'react-native';
import { useRouter, usePathname } from 'expo-router';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { IconSymbol } from '@/components/ui/icon-symbol';
import { Colors } from '@/constants/theme';
import { useColorScheme } from '@/hooks/use-color-scheme';

export function BottomNavigation() {
  const router = useRouter();
  const pathname = usePathname();
  const colorScheme = useColorScheme();
  const colors = Colors[colorScheme ?? 'light'];
  const insets = useSafeAreaInsets();

  const isActive = (path: string) => {
    if (path === '/') {
      return pathname === '/(tabs)' || pathname === '/(tabs)/' || pathname === '/(tabs)/index';
    }
    return pathname?.includes(path);
  };

  return (
    <View style={[styles.container, { backgroundColor: colors.background, borderTopColor: colors.icon, paddingBottom: insets.bottom }]}>
      <TouchableOpacity
        style={styles.button}
        onPress={() => router.push('/(tabs)/' as any)}
        activeOpacity={0.7}>
        <IconSymbol
          name="house.fill"
          size={24}
          color={isActive('/') ? colors.tint : colors.icon}
        />
        <Text style={[styles.label, { color: isActive('/') ? colors.tint : colors.icon }]}>
          Home
        </Text>
      </TouchableOpacity>

      <TouchableOpacity
        style={styles.button}
        onPress={() => router.push('/(tabs)/recipes/create' as any)}
        activeOpacity={0.7}>
        <View style={[styles.addButton, { backgroundColor: colors.tint }]}>
          <IconSymbol name="plus" size={24} color="#fff" />
        </View>
        <Text style={[styles.label, { color: colors.tint }]}>Criar</Text>
      </TouchableOpacity>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flexDirection: 'row',
    justifyContent: 'space-around',
    alignItems: 'center',
    paddingVertical: 12,
    paddingBottom: 24,
    borderTopWidth: 1,
    position: 'absolute',
    bottom: 0,
    left: 0,
    right: 0,
  },
  button: {
    alignItems: 'center',
    justifyContent: 'center',
    flex: 1,
    gap: 4,
  },
  label: {
    fontSize: 12,
    fontWeight: '500',
  },
  addButton: {
    width: 56,
    height: 56,
    borderRadius: 28,
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: -8,
  },
});

