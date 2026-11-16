import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    Calendar,
    Globe,
    Lock,
    Pencil,
    Plus,
    Trash2,
    Tag,
    User as UserIcon,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard } from '@/routes';

type ArticleItem = {
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    status: 'draft' | 'in_review' | 'published' | 'archived';
    statusLabel: string;
    visibility: 'community' | 'public';
    visibilityLabel: string;
    coverImageUrl: string | null;
    publishedAt: string | null;
    createdAt: string;
    author: {
        id: number;
        name: string;
    };
    topic: {
        id: number;
        name: string;
        slug: string;
        color: string | null;
    } | null;
    can: {
        update: boolean;
        publish: boolean;
        delete: boolean;
    };
};

type TopicItem = {
    id: number;
    name: string;
    slug: string;
    color: string | null;
};

type Props = {
    articles: {
        data: ArticleItem[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        current_page: number;
        last_page: number;
    };
    topics: TopicItem[];
    filters: {
        status?: string;
        topic?: string;
    };
    can: {
        create: boolean;
    };
};

export default function ArticlesIndex({ articles, topics, filters, can }: Props) {
    const page = usePage();
    const currentTeam = page.props.currentTeam;
    const teamSlug = currentTeam?.slug ?? '';

    const handleFilterStatus = (status?: string) => {
        router.get(
            `/${teamSlug}/articles`,
            {
                ...filters,
                status: status || undefined,
            },
            { preserveState: true },
        );
    };

    const handleFilterTopic = (topicSlug?: string) => {
        router.get(
            `/${teamSlug}/articles`,
            {
                ...filters,
                topic: topicSlug || undefined,
            },
            { preserveState: true },
        );
    };

    const handleDelete = (article: ArticleItem) => {
        if (confirm(`¿Estás seguro de eliminar el artículo "${article.title}"?`)) {
            router.delete(`/${teamSlug}/articles/${article.slug}`);
        }
    };

    return (
        <>
            <Head title="Artículos de la Comunidad" />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                {/* Header */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                            Artículos de la Comunidad
                        </h1>
                        <p className="text-sm text-neutral-500 dark:text-neutral-400">
                            Crea, gestiona y comparte artículos con tu equipo o publícalos en el portal de noticias.
                        </p>
                    </div>

                    {can.create && (
                        <Button asChild>
                            <Link href={`/${teamSlug}/articles/create`}>
                                <Plus className="mr-2 size-4" />
                                Nuevo Artículo
                            </Link>
                        </Button>
                    )}
                </div>

                {/* Filters */}
                <div className="flex flex-wrap items-center gap-2 border-b border-neutral-200 pb-4 dark:border-neutral-800">
                    <span className="text-xs font-semibold uppercase tracking-wider text-neutral-400">Estado:</span>
                    <Button
                        variant={!filters.status ? 'default' : 'outline'}
                        size="sm"
                        onClick={() => handleFilterStatus(undefined)}
                    >
                        Todos
                    </Button>
                    <Button
                        variant={filters.status === 'published' ? 'default' : 'outline'}
                        size="sm"
                        onClick={() => handleFilterStatus('published')}
                    >
                        Publicados
                    </Button>
                    <Button
                        variant={filters.status === 'draft' ? 'default' : 'outline'}
                        size="sm"
                        onClick={() => handleFilterStatus('draft')}
                    >
                        Borradores
                    </Button>
                    <Button
                        variant={filters.status === 'in_review' ? 'default' : 'outline'}
                        size="sm"
                        onClick={() => handleFilterStatus('in_review')}
                    >
                        En revisión
                    </Button>

                    {topics.length > 0 && (
                        <div className="ml-auto flex flex-wrap items-center gap-1">
                            <span className="text-xs font-semibold uppercase tracking-wider text-neutral-400">Tema:</span>
                            <select
                                value={filters.topic ?? ''}
                                onChange={(e) => handleFilterTopic(e.target.value || undefined)}
                                className="h-8 rounded-md border border-neutral-300 bg-white px-2 text-xs text-neutral-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-200"
                            >
                                <option value="">Todos los temas</option>
                                {topics.map((topic) => (
                                    <option key={topic.id} value={topic.slug}>
                                        {topic.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                    )}
                </div>

                {/* Articles Grid */}
                {articles.data.length === 0 ? (
                    <div className="flex flex-col items-center justify-center rounded-xl border border-dashed border-neutral-300 p-12 text-center dark:border-neutral-700">
                        <div className="rounded-full bg-neutral-100 p-4 dark:bg-neutral-800">
                            <Tag className="size-8 text-neutral-400" />
                        </div>
                        <h3 className="mt-4 text-base font-semibold text-neutral-900 dark:text-neutral-100">
                            No hay artículos disponibles
                        </h3>
                        <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                            {filters.status || filters.topic
                                ? 'No se encontraron artículos con los filtros seleccionados.'
                                : 'Comienza redactando tu primer artículo en esta comunidad.'}
                        </p>
                        {can.create && (
                            <Button asChild className="mt-4">
                                <Link href={`/${teamSlug}/articles/create`}>
                                    <Plus className="mr-2 size-4" />
                                    Crear mi primer artículo
                                </Link>
                            </Button>
                        )}
                    </div>
                ) : (
                    <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        {articles.data.map((article) => (
                            <Card key={article.id} className="flex flex-col overflow-hidden">
                                {article.coverImageUrl && (
                                    <div className="aspect-video w-full overflow-hidden bg-neutral-100 dark:bg-neutral-800">
                                        <img
                                            src={article.coverImageUrl}
                                            alt={article.title}
                                            className="size-full object-cover"
                                        />
                                    </div>
                                )}

                                <CardHeader className="pb-3">
                                    <div className="flex items-center justify-between gap-2">
                                        {article.topic ? (
                                            <Badge
                                                variant="outline"
                                                style={{
                                                    borderColor: article.topic.color ?? undefined,
                                                    color: article.topic.color ?? undefined,
                                                }}
                                            >
                                                {article.topic.name}
                                            </Badge>
                                        ) : (
                                            <span />
                                        )}

                                        <div className="flex items-center gap-1.5">
                                            <Badge
                                                variant={
                                                    article.status === 'published'
                                                        ? 'default'
                                                        : article.status === 'in_review'
                                                          ? 'secondary'
                                                          : 'outline'
                                                }
                                            >
                                                {article.statusLabel}
                                            </Badge>
                                            {article.visibility === 'public' ? (
                                                <span title="Visible en portal público" className="text-emerald-500">
                                                    <Globe className="size-3.5" />
                                                </span>
                                            ) : (
                                                <span title="Exclusivo de la comunidad" className="text-neutral-400">
                                                    <Lock className="size-3.5" />
                                                </span>
                                            )}
                                        </div>
                                    </div>

                                    <CardTitle className="line-clamp-2 text-lg font-semibold hover:text-neutral-700 dark:hover:text-neutral-300">
                                        {article.title}
                                    </CardTitle>

                                    {article.excerpt && (
                                        <CardDescription className="line-clamp-2 text-xs">
                                            {article.excerpt}
                                        </CardDescription>
                                    )}
                                </CardHeader>

                                <CardContent className="mt-auto pb-3 text-xs text-neutral-500 dark:text-neutral-400">
                                    <div className="flex items-center gap-2">
                                        <UserIcon className="size-3.5" />
                                        <span>{article.author.name}</span>
                                    </div>
                                    <div className="mt-1 flex items-center gap-2">
                                        <Calendar className="size-3.5" />
                                        <span>
                                            {article.publishedAt
                                                ? new Date(article.publishedAt).toLocaleDateString()
                                                : new Date(article.createdAt).toLocaleDateString()}
                                        </span>
                                    </div>
                                </CardContent>

                                <CardFooter className="flex items-center justify-between border-t border-neutral-100 pt-3 dark:border-neutral-800">
                                    <div className="flex items-center gap-2">
                                        {article.can.update && (
                                            <Button variant="ghost" size="sm" asChild>
                                                <Link href={`/${teamSlug}/articles/${article.slug}/edit`}>
                                                    <Pencil className="mr-1 size-3.5" />
                                                    Editar
                                                </Link>
                                            </Button>
                                        )}
                                        {article.can.delete && (
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => handleDelete(article)}
                                                className="text-red-500 hover:text-red-600 dark:text-red-400"
                                            >
                                                <Trash2 className="size-3.5" />
                                            </Button>
                                        )}
                                    </div>
                                </CardFooter>
                            </Card>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

ArticlesIndex.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: props.currentTeam ? dashboard(props.currentTeam.slug) : '/',
        },
        {
            title: 'Artículos',
            href: props.currentTeam ? `/${props.currentTeam.slug}/articles` : '#',
        },
    ],
});
