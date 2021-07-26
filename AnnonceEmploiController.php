<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AnnonceEmploiResource;
use App\Models\AnnonceEmploi;
use App\ScoutExtensions\Generators\Agg;
use App\ScoutExtensions\Generators\DSL;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AnnonceEmploiController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function index()
    {
        return AnnonceEmploi::search()->paginate(10);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return AnnonceEmploiResource
     */
    public function show(AnnonceEmploi $annonceEmploi)
    {
        return new AnnonceEmploiResource($annonceEmploi);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|Response
     */
    public function store(Request $request)
    {
        AnnonceEmploi::create($request->all());
        return response()->json([
            'sucess' => 'Annonce créée avec succès'
        ], 200);

    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse|Response
     *
     */
    public function update(Request $request, AnnonceEmploi $annonceEmploi)
    {
        $annonceEmploi->update($request->all());
        return response()->json([
            'sucess' => 'Annonce modifiée avec succès'
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return Response
     */
    public function destroy(AnnonceEmploi $annonceEmploi)
    {
        $annonceEmploi->delete();
        return response($annonceEmploi, 200);
    }

//////////////////////////// Fonctions spécifiques   //////////////////////////////////////


// Recherche par terms(précise) et match(fullText)
    public function terms($field, $value)
    {
        $search = AnnonceEmploi::search()
            ->boolean()
            ->filter(DSL::term($field, $value))
            ->orderBy('ann_datecrea', 'desc')
            //->filter(DSL::term('fnct_id', 192))
            ->aggregate((new Agg)->script('Fonction', "doc['fnct_id'].value+'-'+doc['fnct_lib_parent'].value+'-'+doc['fnct_lib'].value"))
            ->aggregate((new Agg)->script('Region', "doc['gre_id'].value+'-'+doc['region_lib'].value"))
            ->aggregate((new Agg)->script('Département', "doc['gde_id'].value+'-'+doc['departement_lib'].value"))
            ->aggregate((new Agg)->terms('Dates', 'ann_datedebut'))
            ->aggregate((new Agg)->script('Type de contrat', "doc['contrat_lib'].value"))
            ->aggregate((new Agg)->script('Sec', "doc['sec_id'].value+'-'+doc['secteur_lib'].value"))
            ->aggregate((new Agg)->script('Pays', "doc['pay_code'].value+'-'+doc['pays_lib'].value"))
            ->aggregate((new Agg)->script('Expérience', "doc['nexp_id'].value+'-'+doc['experience_lib'].value"))
            ->aggregate((new Agg)->script('Remuneration', "doc['nremu_id'].value+'-'+doc['remuneration_lib'].value"));

        $search->toJson();

        //fire the search
        $resultats = $search->paginate(10);

        //retrieve aggregation results
        //$aggregations = $search->getAggregations();

        $Fonctions = $search->aggregation('Fonction');
        $Regions = $search->aggregation('Region');
        $Departements = $search->aggregation('Département');
        $Dates = $search->aggregation('Dates');
        $Types = $search->aggregation('Type de contrat');
        $Secteurs = $search->aggregation('Sec');
        $Pays = $search->aggregation('Pays');
        $Experiences = $search->aggregation('Expérience');
        $Remunerations = $search->aggregation('Remuneration');

//Résultats
        foreach ($resultats as $resultat) {
            printf("<a><br>Titre de l'annonce: </a>");
            printf($resultat->ann_titre);
            printf("<a><br>Type de l'annonce: </a>");
            printf($resultat->fnct_parente_libelle);
            printf("<br><a>Département: </a>");
            printf("$resultat->gde_id");
            printf("<br><a>Type du contrat: </a>");
            printf($resultat->fnct_libelle);
            printf("<br><a>Logo : </a>");
            printf($resultat->rec_logo_petit);
            printf("<a><br>Description de l'annonce: </a>");
            echo substr("$resultat->ann_description", 0, 500) . '...<br>';
        }

//Aggregations
        printf("<a><br><br><br>Regions : </a><br>");
        echo json_encode($Regions, JSON_UNESCAPED_UNICODE);
        printf("<a><br><br><br>Départements : </a><br>");
        echo json_encode($Departements, JSON_UNESCAPED_UNICODE);
        printf("<a><br><br><br>Dates : </a><br>");
        echo json_encode($Dates, JSON_UNESCAPED_UNICODE);
        printf("<a><br><br><br>Fonctions : </a><br>");
        echo json_encode($Fonctions, JSON_UNESCAPED_UNICODE);
        printf("<a><br><br><br>Types : </a><br>");
        echo json_encode($Types, JSON_UNESCAPED_UNICODE);
        printf("<a><br><br><br>Secteurs : </a><br>");
        echo json_encode($Secteurs, JSON_UNESCAPED_UNICODE);
        printf("<a><br><br><br>Pays : </a><br>");
        echo json_encode($Pays, JSON_UNESCAPED_UNICODE);
        printf("<a><br><br><br>Experiences : </a><br>");
        echo json_encode($Experiences, JSON_UNESCAPED_UNICODE);
        printf("<a><br><br><br>Rémunérations : </a><br>");
        echo json_encode($Remunerations, JSON_UNESCAPED_UNICODE);
    }

    public function match($field, $query)
    {
        $search = AnnonceEmploi::search()
            ->boolean()
            ->should((new DSL)->match($field, $query))
            ->orderBy('ann_datecrea', 'desc')
            //->filter(DSL::term('fnct_id', 192))
            ->aggregate((new Agg)->script('Fonction', "doc['fnct_id'].value+'-'+doc['fnct_lib_parent'].value+'-'+doc['fnct_lib'].value"))
            ->aggregate((new Agg)->script('Region', "doc['gre_id'].value+'-'+doc['region_lib'].value"))
            ->aggregate((new Agg)->script('Département', "doc['gde_id'].value+'-'+doc['departement_lib'].value"))
            ->aggregate((new Agg)->terms('Dates', 'ann_datedebut'))
            ->aggregate((new Agg)->script('Type de contrat', "doc['contrat_lib'].value"))
            ->aggregate((new Agg)->script('Sec', "doc['sec_id'].value+'-'+doc['secteur_lib'].value"))
            ->aggregate((new Agg)->script('Pays', "doc['pay_code'].value+'-'+doc['pays_lib'].value"))
            ->aggregate((new Agg)->script('Expérience', "doc['nexp_id'].value+'-'+doc['experience_lib'].value"))
            ->aggregate((new Agg)->script('Remuneration', "doc['nremu_id'].value+'-'+doc['remuneration_lib'].value"));

        $search->toJson();

        //fire the search
        $resultats = $search->paginate(10);

        //retrieve aggregation results
        //$aggregations = $search->getAggregations();

        $Fonctions = $search->aggregation('Fonction');
        $Regions = $search->aggregation('Region');
        $Departements = $search->aggregation('Département');
        $Dates = $search->aggregation('Dates');
        $Types = $search->aggregation('Type de contrat');
        $Secteurs = $search->aggregation('Sec');
        $Pays = $search->aggregation('Pays');
        $Experiences = $search->aggregation('Expérience');
        $Remunerations = $search->aggregation('Remuneration');

//Résultats
        foreach ($resultats as $resultat) {
            printf("<a><br>Titre de l'annonce: </a>");
            printf($resultat->ann_titre);
            printf("<a><br>Type de l'annonce: </a>");
            echo "$resultat->fnct_parente_libelle";
            printf("<br><a>Département: </a>");
            echo("$resultat->gde_id");
            printf("<br><a>Type du contrat: </a>");
            printf($resultat->fnct_libelle);
            printf("<br><a>Logo : </a>");
            printf($resultat->rec_logo_petit);
            printf("<a><br>Description de l'annonce: </a>");
            echo substr("$resultat->ann_description", 0, 500) . '...<br>';
        }
//Aggregations
        printf("<a><br><br><br>Regions : </a><br>");
        echo json_encode($Regions, JSON_UNESCAPED_UNICODE);
        printf("<a><br><br><br>Départements : </a><br>");
        echo json_encode($Departements, JSON_UNESCAPED_UNICODE);
        printf("<a><br><br><br>Dates : </a><br>");
        echo json_encode($Dates, JSON_UNESCAPED_UNICODE);
        printf("<a><br><br><br>Fonctions : </a><br>");
        echo json_encode($Fonctions, JSON_UNESCAPED_UNICODE);
        printf("<a><br><br><br>Types : </a><br>");
        echo json_encode($Types, JSON_UNESCAPED_UNICODE);
        printf("<a><br><br><br>Secteurs : </a><br>");
        echo json_encode($Secteurs, JSON_UNESCAPED_UNICODE);
        printf("<a><br><br><br>Pays : </a><br>");
        echo json_encode($Pays, JSON_UNESCAPED_UNICODE);
        printf("<a><br><br><br>Experiences : </a><br>");
        echo json_encode($Experiences, JSON_UNESCAPED_UNICODE);
        printf("<a><br><br><br>Rémunérations : </a><br>");
        echo json_encode($Remunerations, JSON_UNESCAPED_UNICODE);
    }

//Annonces par recherche Fulltext
    public function getKeywords($query)
    {
        $field = 'ann_description';
        $this->match($field, $query);
    }

//Annonces par titre de l'offre
    public function getTitre($query)
    {
        $field = 'ann_titre';
        $this->match($field, $query);
    }

//Annonce par Id
    public function getId($id)
    {
        $search = AnnonceEmploi::search()
            ->boolean()
            ->filter(DSL::term('idAnnonce', $id));
        $search->toJson();
        $resultat = $search->paginate(10);
        foreach ($resultat as $res) {
            printf("<a><br>Titre de l'annonce: </a>");
            printf($res->ann_titre);
            printf("<a><br>Type de l'annonce: </a>");
            echo "$res->fnct_parente_libelle";
            printf("<br><a>Département: </a>");
            echo("$res->gde_id");
            printf("<br><a>Type du contrat: </a>");
            printf($res->fnct_libelle);
            printf("<br><a>Logo : </a>");
            printf($res->rec_logo_petit);
            printf("<a><br>Description de l'annonce: </a>");
            echo substr("$res->ann_description", 0, 500) . '...<br>';
        }
    }

//Annnonces par fonction
    public function getFonction($value)
    {
        $field = 'fnct_id';
        $this->terms($field, $value);
    }

    //Annonces par région
    public function getRegion($value)
    {
        $field = 'gre_id';
        $this->terms($field, $value);

    }


//Annonces par date de publication
    public function getDate()
    {
        //   $annonce = AnnonceEmploi::search()->orderBy('ann_datecrea', 'desc')->paginate(10);
        //   return response($annonce, 200);
    }
}

