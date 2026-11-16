import { Head, Link, useForm, usePage } from '@inertiajs/react';
import React, { useState } from 'react';
import { ArrowLeft, Image as ImageIcon, UploadCloud } from 'lucide-react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';

type TopicItem = {
    id: number;
    name: string;
    slug: string;
    color: string | null;
};

type SelectOption = {
    value: string;
    label: string;
};

type Props = {
    topics: TopicItem[];
    statuses: SelectOption[];
    visibilities: SelectOption[];
    canPublish: boolean;
};

export default function ArticleCreate({ topics, statuses, visibilities, canPublish }: Props) {
    const page = usePage();
    const currentTeam = page.props.currentTeam;
    const teamSlug = currentTeam?.slug ?? '';

    const [imagePreview, setImagePreview] = useState<string | null>(null);

    const { data, setData, post, processing, errors } = useForm({
        title: '',
        topic_id: topics[0]?.id ? String(topics[0].id) : '',
        excerpt: '',
        content: '',
        visibility: 'community',
        status: 'draft',
        cover_image: null as File | null,
    });

    const handleImageChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            setData('cover_image', file);
            const reader = new FileReader();
            reader.onloadend = () => {
                setImagePreview(reader.result as string);
            };
            reader.readAsDataURL(file);
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(`/${teamSlug}/articles`, {
            forceFormData: true,
        });
    };

    return (
        <>
            <Head title="Nuevo Artículo" />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex items-center gap-3">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={`/${teamSlug}/articles`}>
                            <ArrowLeft className="mr-1 size-4" />
                            Volver
                        </Link>
                    </Button>
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-neutral-900 dark:text-neutral-100">
                            Crear Nuevo Artículo
                        </h1>
                        <p className="text-sm text-neutral-500 dark:text-neutral-400">
                            Redacta contenido para tu comunidad o compártelo en el portal general.
                        </p>
                    </div>
                </div>

                <form onSubmit={handleSubmit} className="grid gap-6 lg:grid-cols-3">
                    {/* Main content column */}
                    <div className="space-y-6 lg:col-span-2">
                        <Card>
                            <CardHeader>
                                <CardTitle>Contenido del Artículo</CardTitle>
                                <CardDescription>Define el título, resumen y cuerpo de tu noticia.</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-1">
                                    <Label htmlFor="title">Título *</Label>
                                    <Input
                                        id="title"
                                        value={data.title}
                                        onChange={(e) => setData('title', e.target.value)}
                                        placeholder="Ej: Nuevos avances comunitarios en tecnología..."
                                        required
                                    />
                                    <InputError message={errors.title} />
                                </div>

                                <div className="space-y-1">
                                    <Label htmlFor="excerpt">Resumen breve (Extracto)</Label>
                                    <textarea
                                        id="excerpt"
                                        value={data.excerpt}
                                        onChange={(e) => setData('excerpt', e.target.value)}
                                        rows={2}
                                        placeholder="Un breve resumen que aparecerá en los listados y portadas..."
                                        className="w-full rounded-md border border-neutral-300 bg-white p-3 text-sm text-neutral-900 focus:border-neutral-900 focus:outline-none focus:ring-1 focus:ring-neutral-900 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-100"
                                    />
                                    <InputError message={errors.excerpt} />
                                </div>

                                <div className="space-y-1">
                                    <Label htmlFor="content">Cuerpo del Artículo *</Label>
                                    <textarea
                                        id="content"
                                        value={data.content}
                                        onChange={(e) => setData('content', e.target.value)}
                                        rows={12}
                                        placeholder="Escribe el artículo completo aquí..."
                                        required
                                        className="w-full rounded-md border border-neutral-300 bg-white p-3 text-sm text-neutral-900 focus:border-neutral-900 focus:outline-none focus:ring-1 focus:ring-neutral-900 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-100"
                                    />
                                    <InputError message={errors.content} />
                                </div>
                            </CardContent>
                        </Card>

                        {/* Image upload */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Imagen de Portada (S3)</CardTitle>
                                <CardDescription>Se almacenará en AWS S3 a través del emulador local.</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {imagePreview ? (
                                    <div className="relative aspect-video w-full overflow-hidden rounded-lg border border-neutral-200 dark:border-neutral-800">
                                        <img src={imagePreview} alt="Vista previa" className="size-full object-cover" />
                                        <Button
                                            type="button"
                                            variant="secondary"
                                            size="sm"
                                            onClick={() => {
                                                setImagePreview(null);
                                                setData('cover_image', null);
                                            }}
                                            className="absolute right-2 top-2"
                                        >
                                            Quitar imagen
                                        </Button>
                                    </div>
                                ) : (
                                    <label
                                        htmlFor="cover_image"
                                        className="flex cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-neutral-300 p-8 text-center hover:bg-neutral-50 dark:border-neutral-700 dark:hover:bg-neutral-800/50"
                                    >
                                        <UploadCloud className="size-10 text-neutral-400" />
                                        <span className="mt-2 text-sm font-medium text-neutral-700 dark:text-neutral-300">
                                            Haz clic para seleccionar una imagen
                                        </span>
                                        <span className="text-xs text-neutral-500">PNG, JPG, WebP hasta 4MB</span>
                                        <input
                                            id="cover_image"
                                            type="file"
                                            accept="image/*"
                                            onChange={handleImageChange}
                                            className="hidden"
                                        />
                                    </label>
                                )}
                                <InputError message={errors.cover_image} />
                            </CardContent>
                        </Card>
                    </div>

                    {/* Sidebar column */}
                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Configuración Editorial</CardTitle>
                                <CardDescription>Tema, visibilidad y estado de publicación.</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-1">
                                    <Label htmlFor="topic">Tema / Categoría</Label>
                                    <select
                                        id="topic"
                                        value={data.topic_id}
                                        onChange={(e) => setData('topic_id', e.target.value)}
                                        className="h-10 w-full rounded-md border border-neutral-300 bg-white px-3 text-sm text-neutral-900 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-100"
                                    >
                                        <option value="">Sin tema asignado</option>
                                        {topics.map((topic) => (
                                            <option key={topic.id} value={topic.id}>
                                                {topic.name}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.topic_id} />
                                </div>

                                <div className="space-y-1">
                                    <Label htmlFor="visibility">Alcance / Visibilidad</Label>
                                    <select
                                        id="visibility"
                                        value={data.visibility}
                                        onChange={(e) => setData('visibility', e.target.value)}
                                        className="h-10 w-full rounded-md border border-neutral-300 bg-white px-3 text-sm text-neutral-900 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-100"
                                    >
                                        {visibilities.map((vis) => (
                                            <option key={vis.value} value={vis.value}>
                                                {vis.label}
                                            </option>
                                        ))}
                                    </select>
                                    <p className="text-xs text-neutral-500">
                                        "Comunidad": solo miembros del equipo. "Público": visible en la portada del portal.
                                    </p>
                                    <InputError message={errors.visibility} />
                                </div>

                                <div className="space-y-1">
                                    <Label htmlFor="status">Estado</Label>
                                    <select
                                        id="status"
                                        value={data.status}
                                        onChange={(e) => setData('status', e.target.value)}
                                        className="h-10 w-full rounded-md border border-neutral-300 bg-white px-3 text-sm text-neutral-900 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-100"
                                    >
                                        {statuses.map((st) => {
                                            if (st.value === 'published' && !canPublish) {
                                                return null;
                                            }
                                            return (
                                                <option key={st.value} value={st.value}>
                                                    {st.label}
                                                </option>
                                            );
                                        })}
                                    </select>
                                    {!canPublish && (
                                        <p className="text-xs text-amber-600 dark:text-amber-400">
                                            Tu rol actual no tiene permisos para publicar directamente; puedes guardar como borrador o enviar a revisión.
                                        </p>
                                    )}
                                    <InputError message={errors.status} />
                                </div>

                                <div className="pt-4">
                                    <Button type="submit" disabled={processing} className="w-full">
                                        {processing ? 'Guardando...' : 'Crear Artículo'}
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </form>
            </div>
        </>
    );
}

ArticleCreate.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: props.currentTeam ? dashboard(props.currentTeam.slug) : '/',
        },
        {
            title: 'Artículos',
            href: props.currentTeam ? `/${props.currentTeam.slug}/articles` : '#',
        },
        {
            title: 'Crear',
            href: '#',
        },
    ],
});
