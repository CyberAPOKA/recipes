import { useState, useEffect } from "react";
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  TouchableOpacity,
  ActivityIndicator,
  RefreshControl,
  TextInput,
  Alert,
} from "react-native";
import { useRouter } from "expo-router";
import { Image } from "expo-image";
import { IconSymbol } from "@/components/ui/icon-symbol";
import { apiService, Recipe } from "@/services/api";
import { Colors } from "@/constants/theme";
import { useColorScheme } from "@/hooks/use-color-scheme";
import { useAuth } from "@/contexts/AuthContext";
import { BottomNavigation } from "@/components/BottomNavigation";

export default function HomeScreen() {
  const router = useRouter();
  const { isAuthenticated, loading: authLoading, logout, user } = useAuth();
  const colorScheme = useColorScheme();
  const colors = Colors[colorScheme ?? "light"];

  const [recipes, setRecipes] = useState<Recipe[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [search, setSearch] = useState("");
  const [searchDebounced, setSearchDebounced] = useState("");
  const [page, setPage] = useState(1);
  const [hasMore, setHasMore] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);

  useEffect(() => {
    if (!authLoading && !isAuthenticated) {
      router.replace("/auth/login" as any);
    }
  }, [authLoading, isAuthenticated, router]);

  const handleLogout = () => {
    Alert.alert("Sair", "Tem certeza que deseja sair?", [
      { text: "Cancelar", style: "cancel" },
      {
        text: "Sair",
        style: "destructive",
        onPress: async () => {
          await logout();
          router.replace("/auth/login" as any);
        },
      },
    ]);
  };

  const loadRecipes = async (pageNum: number = 1, reset: boolean = false) => {
    try {
      if (reset) {
        setLoading(true);
      } else if (pageNum > 1) {
        setLoadingMore(true);
      }

      const response = await apiService.getRecipes(
        pageNum,
        searchDebounced || undefined
      );
      const newRecipes = response.data;

      if (reset) {
        setRecipes(newRecipes);
      } else {
        setRecipes((prev) => [...prev, ...newRecipes]);
      }

      if (response.meta) {
        setHasMore(pageNum < response.meta.last_page);
      } else {
        setHasMore(newRecipes.length > 0);
      }
    } catch (error) {
      console.error("Erro ao carregar receitas:", error);
    } finally {
      setLoading(false);
      setLoadingMore(false);
      setRefreshing(false);
    }
  };

  // Debounce da busca
  useEffect(() => {
    const timer = setTimeout(() => {
      setSearchDebounced(search);
      setPage(1); // Reset page quando busca muda
    }, 500); // 500ms de delay

    return () => clearTimeout(timer);
  }, [search]);

  useEffect(() => {
    if (isAuthenticated) {
      setPage(1); // Garantir que começa na página 1
      loadRecipes(1, true);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [searchDebounced, isAuthenticated]);

  const handleRefresh = () => {
    setRefreshing(true);
    setPage(1);
    loadRecipes(1, true);
  };

  const handleLoadMore = () => {
    if (!loadingMore && hasMore) {
      const nextPage = page + 1;
      setPage(nextPage);
      loadRecipes(nextPage, false);
    }
  };

  const renderRecipeItem = ({ item }: { item: Recipe }) => (
    <TouchableOpacity
      style={[styles.recipeCard, { backgroundColor: colors.background }]}
      onPress={() => router.push(`/(tabs)/recipes/${item.id}` as any)}
      activeOpacity={0.7}
    >
      {item.image && (
        <Image
          source={{ uri: item.image }}
          style={styles.recipeImage}
          contentFit="cover"
        />
      )}
      <View style={styles.recipeContent}>
        <Text
          style={[styles.recipeName, { color: colors.text }]}
          numberOfLines={2}
        >
          {item.name || "Receita sem nome"}
        </Text>
        <View style={styles.recipeMeta}>
          {item.prep_time_minutes && (
            <View style={styles.metaItem}>
              <IconSymbol name="clock" size={14} color={colors.icon} />
              <Text style={[styles.metaText, { color: colors.icon }]}>
                {item.prep_time_minutes} min
              </Text>
            </View>
          )}
          {item.servings && (
            <View style={styles.metaItem}>
              <IconSymbol name="person.fill" size={14} color={colors.icon} />
              <Text style={[styles.metaText, { color: colors.icon }]}>
                {item.servings} porções
              </Text>
            </View>
          )}
        </View>
        {item.category && (
          <Text style={[styles.categoryText, { color: colors.tint }]}>
            {item.category.name}
          </Text>
        )}
      </View>
    </TouchableOpacity>
  );

  if (authLoading || (loading && recipes.length === 0)) {
    return (
      <View
        style={[
          styles.container,
          styles.centerContent,
          { backgroundColor: colors.background },
        ]}
      >
        <ActivityIndicator size="large" color={colors.tint} />
      </View>
    );
  }

  if (!isAuthenticated) {
    return null;
  }

  return (
    <View style={[styles.container, { backgroundColor: colors.background }]}>
      <View style={styles.header}>
        <View style={styles.headerLeft}>
          <Text style={[styles.title, { color: colors.text }]}>Receitas</Text>
          {user && (
            <Text style={[styles.userName, { color: colors.icon }]}>
              Olá, {user.name}
            </Text>
          )}
        </View>
        <View style={styles.headerRight}>
          <TouchableOpacity
            style={[styles.addButton, { backgroundColor: colors.tint }]}
            onPress={() => router.push("/(tabs)/recipes/create" as any)}
          >
            <IconSymbol name="plus" size={20} color="#fff" />
          </TouchableOpacity>
          <TouchableOpacity style={styles.logoutButton} onPress={handleLogout}>
            <IconSymbol
              name="rectangle.portrait.and.arrow.right"
              size={20}
              color={colors.icon}
            />
          </TouchableOpacity>
        </View>
      </View>

      <View
        style={[styles.searchContainer, { backgroundColor: colors.background }]}
      >
        <IconSymbol name="magnifyingglass" size={20} color={colors.icon} />
        <TextInput
          style={[styles.searchInput, { color: colors.text }]}
          placeholder="Buscar receitas..."
          placeholderTextColor={colors.icon}
          value={search}
          onChangeText={setSearch}
        />
      </View>

      <FlatList
        data={recipes}
        renderItem={renderRecipeItem}
        keyExtractor={(item) => item.id.toString()}
        contentContainerStyle={styles.listContent}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={handleRefresh} />
        }
        onEndReached={handleLoadMore}
        onEndReachedThreshold={0.5}
        ListFooterComponent={
          loadingMore ? (
            <View style={styles.footerLoader}>
              <ActivityIndicator size="small" color={colors.tint} />
            </View>
          ) : null
        }
        ListEmptyComponent={
          !loading ? (
            <View style={styles.emptyContainer}>
              <Text style={[styles.emptyText, { color: colors.icon }]}>
                Nenhuma receita encontrada
              </Text>
            </View>
          ) : null
        }
      />
      <BottomNavigation />
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  centerContent: {
    justifyContent: "center",
    alignItems: "center",
  },
  header: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    padding: 16,
    paddingTop: 60,
  },
  headerLeft: {
    flex: 1,
  },
  headerRight: {
    flexDirection: "row",
    gap: 8,
    alignItems: "center",
  },
  title: {
    fontSize: 28,
    fontWeight: "bold",
  },
  userName: {
    fontSize: 12,
    marginTop: 4,
  },
  addButton: {
    width: 40,
    height: 40,
    borderRadius: 20,
    justifyContent: "center",
    alignItems: "center",
  },
  logoutButton: {
    width: 40,
    height: 40,
    justifyContent: "center",
    alignItems: "center",
  },
  searchContainer: {
    flexDirection: "row",
    alignItems: "center",
    marginHorizontal: 16,
    marginBottom: 16,
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: "#e0e0e0",
  },
  searchInput: {
    flex: 1,
    marginLeft: 8,
    fontSize: 16,
  },
  listContent: {
    padding: 16,
    paddingTop: 0,
  },
  recipeCard: {
    marginBottom: 16,
    borderRadius: 12,
    overflow: "hidden",
    shadowColor: "#000",
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 3,
  },
  recipeImage: {
    width: "100%",
    height: 200,
  },
  recipeContent: {
    padding: 12,
  },
  recipeName: {
    fontSize: 18,
    fontWeight: "600",
    marginBottom: 8,
  },
  recipeMeta: {
    flexDirection: "row",
    gap: 16,
    marginBottom: 8,
  },
  metaItem: {
    flexDirection: "row",
    alignItems: "center",
    gap: 4,
  },
  metaText: {
    fontSize: 14,
  },
  categoryText: {
    fontSize: 12,
    fontWeight: "500",
  },
  footerLoader: {
    paddingVertical: 20,
    alignItems: "center",
  },
  emptyContainer: {
    paddingVertical: 40,
    alignItems: "center",
  },
  emptyText: {
    fontSize: 16,
  },
});
